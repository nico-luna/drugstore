export interface User {
    idusuario: number;
    nombre: string;
    correo: string;
    usuario: string;
    es_admin: boolean;
    estado: boolean;
    permisos: string[];
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string;
        error?: string;
    };
}
