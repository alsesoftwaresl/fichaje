<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La verificación de email solo debe exigirse a quien se registre a
     * partir de ahora. Los admin_empresa que ya existían (dados de alta a
     * mano por super_admin, antes de este requisito) quedan verificados para
     * no bloquearles el acceso al desplegar.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('rol', 'admin_empresa')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Irreversible a propósito: no hay forma de saber cuáles estaban sin verificar.
    }
};
