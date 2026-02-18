import { createContext, useContext, useState, useEffect } from 'react';
import { AppShell, useMantineTheme } from '@mantine/core';
import { useDisclosure, useMediaQuery } from '@mantine/hooks';
import Sidebar from './Sidebar';
import Header from './Header';
import Footer from './Footer';
import FlashMessages from '../FlashMessages';

// Layout Context for sharing state across components
const LayoutContext = createContext(null);

export const useLayout = () => {
    const context = useContext(LayoutContext);
    if (!context) {
        throw new Error('useLayout must be used within AdminLayout');
    }
    return context;
};

export default function AdminLayout({
    children,
    navItems = [],
    user = null,
}) {
    const theme = useMantineTheme();
    const [mobileOpened, { toggle: toggleMobile, close: closeMobile }] = useDisclosure();
    const [desktopOpened, setDesktopOpened] = useState(() => {
        if (typeof window !== 'undefined') {
            const saved = localStorage.getItem('sidebar-collapsed');
            return saved !== 'true';
        }
        return true;
    });
    const isMobile = useMediaQuery('(max-width: 768px)');

    // Persist sidebar state
    useEffect(() => {
        localStorage.setItem('sidebar-collapsed', !desktopOpened);
    }, [desktopOpened]);

    const toggleDesktop = () => setDesktopOpened((prev) => !prev);

    const contextValue = {
        mobileOpened,
        desktopOpened,
        toggleMobile,
        toggleDesktop,
        closeMobile,
        isMobile,
        navItems,
        user,
    };

    return (
        <LayoutContext.Provider value={contextValue}>
            <AppShell
                header={{ height: 60 }}
                navbar={{
                    width: desktopOpened ? 260 : 80,
                    breakpoint: 'sm',
                    collapsed: { mobile: !mobileOpened },
                }}
                footer={{ height: 50 }}
                padding="md"
                styles={{
                    main: {
                        backgroundColor: theme.colorScheme === 'dark'
                            ? theme.colors.dark[8]
                            : theme.colors.gray[0],
                        minHeight: 'calc(100vh - 110px)',
                    },
                }}
            >
                <Header />
                <Sidebar />
                <AppShell.Main>
                    <FlashMessages />
                    {children}
                </AppShell.Main>
                <Footer />
            </AppShell>
        </LayoutContext.Provider>
    );
}
