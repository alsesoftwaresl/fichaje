<x-legal.layout :title="'Contrato de encargo de tratamiento'">
    <p>Versión: {{ config('legal.version_encargo') }}</p>

    <p>
        Conforme al artículo 28 del RGPD, este documento regula la relación entre el Cliente
        (Responsable del tratamiento) y {{ config('app.name') }} (Encargado del tratamiento) en
        relación con los datos personales de los empleados del Cliente tratados a través de la
        plataforma.
    </p>

    <p><strong>1. Objeto y duración.</strong> El Encargado tratará los datos únicamente para
    prestar el servicio de registro de jornada, durante la vigencia del contrato de servicio.</p>

    <p><strong>2. Instrucciones.</strong> El Encargado tratará los datos siguiendo las instrucciones
    documentadas del Responsable y no los utilizará para fines propios.</p>

    <p><strong>3. Medidas de seguridad.</strong> El Encargado aplica medidas técnicas y
    organizativas apropiadas: cifrado en tránsito, control de accesos, copias de seguridad y
    registro append-only (no manipulable) de los fichajes.</p>

    <p><strong>4. Subencargados.</strong> [Pendiente: listar proveedores de infraestructura, si
    los hubiera, y el régimen de autorización.]</p>

    <p><strong>5. Devolución y supresión de datos.</strong> [Pendiente de definir: procedimiento al
    finalizar el contrato.]</p>

    <p><strong>6. Notificación de violaciones de seguridad.</strong> El Encargado notificará al
    Responsable sin dilación indebida cualquier violación de seguridad de la que tenga
    conocimiento.</p>
</x-legal.layout>
