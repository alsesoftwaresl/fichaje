<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ausencia;
use App\Models\Cita;
use App\Models\Fichaje;
use App\Models\LegalAceptacion;
use App\Models\Nomina;
use App\Models\User;
use App\Support\Tenant;

/**
 * Todos los datos personales de una persona en un formato estructurado y de uso común
 * (JSON), para atender el derecho de acceso (art. 15 RGPD) y el de portabilidad (art. 20).
 *
 * Incluye su perfil, todo su registro de jornada (con las horas originales, las
 * correcciones y quién las hizo), ausencias, avisos de salida, la lista de sus nóminas (los
 * PDF se descargan aparte) y las acciones registradas sobre ella. No incluye secretos (PIN,
 * contraseña, verificación en dos pasos) ni datos de otras personas.
 */
class DatosPersonales
{
    /**
     * @return array<string, mixed>
     */
    public static function de(User $usuario): array
    {
        Tenant::set($usuario->empresa_id);

        $fichajes = Fichaje::with(['correcciones.corregidoPor', 'registradoPor'])
            ->where('user_id', $usuario->id)
            ->orderBy('fecha_hora')
            ->get();

        return [
            'generado_el' => now()->toIso8601String(),
            'responsable_del_tratamiento' => $usuario->empresa ? [
                'empresa' => $usuario->empresa->nombre,
                'nif' => $usuario->empresa->nif,
                'contacto' => $usuario->empresa->email_contacto,
            ] : null,
            'persona' => [
                'nombre' => $usuario->name,
                'email' => $usuario->email,
                'dni_nie' => $usuario->dni_nie,
                'rol' => $usuario->rol,
                'activo' => $usuario->activo,
                'alta' => $usuario->created_at?->toIso8601String(),
                'horario' => $usuario->tieneHorario() ? [
                    'tramos' => $usuario->tramos(),
                    'dias_laborables' => $usuario->dias_laborables,
                ] : null,
                'verificacion_en_dos_pasos' => $usuario->tieneDosPasos(),
            ],
            'registro_de_jornada' => $fichajes->map(fn (Fichaje $f) => [
                'tipo' => $f->tipo,
                'hora_registrada' => $f->fecha_hora->format('Y-m-d H:i:s'),
                'origen' => $f->origen,
                'ip' => $f->ip_address,
                'anadido_por' => $f->registradoPor?->name,
                'motivo' => $f->motivo,
                'sello_integridad' => $f->huella,
                'correcciones' => $f->correcciones->map(fn ($c) => [
                    'hora_corregida' => $c->fecha_hora_corregida->format('Y-m-d H:i:s'),
                    'motivo' => $c->motivo,
                    'corregido_por' => $c->corregidoPor?->name,
                    'fecha' => $c->created_at?->toIso8601String(),
                ])->all(),
            ])->all(),
            'ausencias' => Ausencia::where('user_id', $usuario->id)->orderBy('fecha_inicio')->get()
                ->map(fn (Ausencia $a) => $a->only(['tipo', 'fecha_inicio', 'fecha_fin', 'motivo', 'estado', 'motivo_rechazo']))
                ->all(),
            'avisos_de_salida' => Cita::where('user_id', $usuario->id)->orderBy('fecha')->get()
                ->map(fn (Cita $c) => ['fecha' => $c->fecha->toDateString(), 'horas' => $c->rango(), 'motivo' => $c->motivo, 'anulado' => $c->anulada_en !== null])
                ->all(),
            'nominas' => Nomina::where('user_id', $usuario->id)->orderBy('periodo')->get()
                ->map(fn (Nomina $n) => ['periodo' => $n->periodo->format('Y-m'), 'descripcion' => $n->descripcion, 'descargada' => $n->descargada_en?->toIso8601String()])
                ->all(),
            'aceptaciones_legales' => LegalAceptacion::where('user_id', $usuario->id)->get()
                ->map(fn ($a) => $a->only(['documento', 'version', 'aceptado_at', 'ip_address']))
                ->all(),
            'acciones_registradas' => AuditLog::where('objeto_tipo', User::class)->where('objeto_id', $usuario->id)
                ->orderBy('created_at')->get()
                ->map(fn ($l) => ['accion' => $l->accion, 'fecha' => $l->created_at?->toIso8601String()])
                ->all(),
        ];
    }
}
