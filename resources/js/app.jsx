import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { MantineProvider, createTheme } from '@mantine/core';
import { ThemeProvider } from './theme';

import { Notifications } from '@mantine/notifications';
import '@mantine/notifications/styles.css';
import '@mantine/core/styles.css';
import '../css/app.css';

// Base Mantine theme (no primaryColor to avoid validation errors - we handle colors ourselves)
const mantineTheme = createTheme({
    fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
    headings: {
        fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
    },
});

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const page = pages[`./Pages/${name}.jsx`];
        if (!page) {
            throw new Error(`Page not found: ${name}. Check that ./Pages/${name}.jsx exists.`);
        }
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ThemeProvider>
                <MantineProvider theme={mantineTheme} forceColorScheme="light">
                    <Notifications position="top-right" />
                    <App {...props} />
                </MantineProvider>
            </ThemeProvider>
        );
    },
});
