/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_URL: string;
    readonly VITE_APP_TITLE: string;
    readonly VITE_APP_LOCALE: string;
    readonly VITE_PUSHER_APP_KEY: string;
    readonly VITE_PUSHER_APP_CLUSTER: string;
    readonly VITE_APP_ENV: string;
    readonly VITE_PUSHER_PORT: string;
    readonly VITE_KARAOKE_API_URL?: string;
    readonly VITE_BRAND_NAME: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv
}
