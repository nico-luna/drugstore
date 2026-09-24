export interface User {
    idusuario: number;
    nombre: string;
    correo: string;
    usuario: string;
    es_admin: boolean;
    is_platform_admin: boolean;
    estado: boolean;
    role: string | null;
    permisos: string[];
}

export interface TenantAccount {
    id: number;
    name: string;
    role: string;
}

export interface TenantStore {
    id: number;
    name: string;
}

export interface TenantContext {
    account: TenantAccount;
    store: TenantStore;
    accounts: TenantAccount[];
    stores: TenantStore[];
}

export interface PageProps extends Record<string, unknown> {
    auth: {
        user: User | null;
    };
    tenant: TenantContext | null;
    flash: {
        success?: string;
        error?: string;
    };
}
