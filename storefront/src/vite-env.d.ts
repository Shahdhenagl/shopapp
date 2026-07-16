/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Override the API base when developing against a remote backend. */
  readonly VITE_API_BASE_URL?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
