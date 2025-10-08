/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_APP_NAME: string
  readonly VITE_APP_URL: string
  // Add other VITE_ variables as needed
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}