import { defineConfig } from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import vuePlugin from "@vitejs/plugin-vue";
import commonjs from '@rollup/plugin-commonjs';
import { fileURLToPath } from 'url';
import Components from 'unplugin-vue-components/vite';

/* if you're using React */
// import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        commonjs(),
        Components({}),
        /* react(), // if you're using React */
        vuePlugin(),
        symfonyPlugin({
            stimulus: true,
        }),
    ],
    css: {
        preprocessorOptions: {
        }
    },
    resolve: {
        alias: {
            "node_modules/bootstrap/scss/": fileURLToPath(new URL("./node_modules/bootstrap/scss/", import.meta.url)),
        }
    },
    build: {
        //target: "ES2022",
        rollupOptions: {
            // session — Bootstrap + session timeout (all layout pages)
            // admin — bootstrap tooltips + user list table filter
            // importFramework — /cfdoc import wizard (SaltLocal); load via Twig only
            // importPage — import route modal only
            // signup / systemLogs — page-specific modules (systemLogs uses DataTables 3 standalone)
            // main — global application.scss (not the admin JS entry)
            input: {
                session: "./assets/js/site.js",
                admin: "./assets/js/main.js",
                importFramework: "./assets/js/import-framework.js",
                importPage: "./assets/js/import-page.js",
                signup: "./assets/js/signup.js",
                systemLogs: "./assets/js/system-logs.js",
                main: "./assets/sass/application.scss",
                credentialcss: "./assets/sass/credential.scss",
                credential: "./assets/js/credential.js",
                app: "./assets/app.js",
            },
        },
        optimizeDeps: {
        },
        commonjsOptions: {
            include: [],
        }
    },
});
