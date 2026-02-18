import { useState } from 'react';
import { AppShell, NavLink, ScrollArea, Stack, Text, Box, Tooltip, ActionIcon, Group } from '@mantine/core';
import { IconMap, IconStairs, IconBuilding } from '@tabler/icons-react';
import { Link, usePage } from '@inertiajs/react';
import { useLayout } from './AdminLayout';
import { useTheme } from '../../theme';
import FetchProgressModal from '../UI/FetchProgressModal';

// Collapse toggle icon
const CollapseIcon = ({ collapsed }) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
        style={{
            transform: collapsed ? 'rotate(180deg)' : 'rotate(0deg)',
            transition: 'transform 0.2s ease',
        }}
    >
        <path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>
    </svg>
);

// Icon components
const icons = {
    dashboard: () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>
        </svg>
    ),
    users: () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
    ),
    receipt: () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>
        </svg>
    ),
    currency: () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/>
        </svg>
    ),
    chart: () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>
        </svg>
    ),
    settings: () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>
        </svg>
    ),
    tool: () => (
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>
    ),
    map: () => <IconMap size={20} stroke={2} />,
    stairs: () => <IconStairs size={20} stroke={2} />,
    building: () => <IconBuilding size={20} stroke={2} />,
};

const getIcon = (iconName, isActive, themeColor) => {
    const IconComponent = icons[iconName];
    if (!IconComponent) return null;

    return (
        <Box style={{ color: isActive ? themeColor : undefined }}>
            <IconComponent />
        </Box>
    );
};

export default function Sidebar() {
    const { url } = usePage();
    const { desktopOpened, toggleDesktop, closeMobile, navItems } = useLayout();
    const { ui, colorConfig } = useTheme();
    const collapsed = !desktopOpened;

    // Fetch modal state
    const [fetchModal, setFetchModal] = useState({ opened: false, url: '', label: '' });

    const openFetchModal = (href, label) => {
        const cleanLabel = label.replace('→ ', '').replace('Fetch ', '');
        setFetchModal({ opened: true, url: href, label: cleanLabel });
    };

    const closeFetchModal = () => {
        setFetchModal({ opened: false, url: '', label: '' });
    };

    const isActive = (href) => {
        return url.startsWith(href);
    };

    return (
        <>
        <AppShell.Navbar
            p="md"
            style={{
                background: ui.sidebarBg,
                backdropFilter: 'blur(10px)',
                borderRight: `1px solid ${ui.border}`,
            }}
        >
            {/* Navigation */}
            <AppShell.Section grow component={ScrollArea}>
                <Stack gap={4}>
                    {navItems.map((item) => {
                        const hasChildren = item.children && item.children.length > 0;
                        const active = isActive(item.href) || (hasChildren && item.children.some(child => isActive(child.href)));

                        // Recursive function to render children could be here, but for 1 level deep:
                        const childrenItems = hasChildren ? item.children.map(child => {
                            const isFetchAction = child.action === 'fetch';
                            return (
                                <NavLink
                                    key={child.href}
                                    component={isFetchAction ? 'button' : Link}
                                    href={isFetchAction ? undefined : child.href}
                                    label={child.label}
                                    active={isActive(child.href)}
                                    onClick={() => {
                                        closeMobile();
                                        if (isFetchAction) {
                                            openFetchModal(child.href, child.label);
                                        }
                                    }}
                                    style={{
                                        borderRadius: 8,
                                        fontSize: '0.9em',
                                        fontWeight: isActive(child.href) ? 600 : undefined,
                                        cursor: isFetchAction ? 'pointer' : undefined,
                                    }}
                                />
                            );
                        }) : null;

                        const navContent = (
                            <NavLink
                                key={item.href}
                                component={hasChildren ? 'div' : Link}
                                href={hasChildren ? undefined : item.href}
                                label={collapsed ? '' : item.label}
                                leftSection={getIcon(item.icon, active, colorConfig.primary)}
                                active={active}
                                childrenOffset={collapsed ? 0 : 28}
                                defaultOpened={active}
                                onClick={hasChildren ? undefined : closeMobile}
                                style={{
                                    borderRadius: 8,
                                    marginBottom: 4,
                                    justifyContent: collapsed ? 'center' : 'flex-start',
                                    padding: collapsed ? '12px' : undefined,
                                    backgroundColor: active && !hasChildren ? colorConfig.light : 'transparent',
                                    color: active ? colorConfig.primary : undefined,
                                    fontWeight: active ? 600 : undefined,
                                }}
                            >
                                {!collapsed && childrenItems}
                            </NavLink>
                        );

                        return collapsed ? (
                            <Tooltip key={item.href} label={item.label} position="right" withArrow>
                                {navContent}
                            </Tooltip>
                        ) : (
                            navContent
                        );
                    })}
                </Stack>
            </AppShell.Section>

            {/* Footer section with collapse toggle */}
            <AppShell.Section>
                <Box
                    pt="md"
                    style={{
                        borderTop: `1px solid ${ui.border}`,
                        display: 'flex',
                        justifyContent: collapsed ? 'center' : 'space-between',
                        alignItems: 'center',
                    }}
                >
                    {!collapsed && (
                        <Text size="xs" c="dimmed">
                            v1.0.0
                        </Text>
                    )}
                    <Tooltip label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'} position="right">
                        <ActionIcon
                            variant="subtle"
                            size="md"
                            radius="md"
                            onClick={toggleDesktop}
                            style={{
                                color: ui.textMuted,
                            }}
                        >
                            <CollapseIcon collapsed={collapsed} />
                        </ActionIcon>
                    </Tooltip>
                </Box>
            </AppShell.Section>
        </AppShell.Navbar>

            {/* Fetch Progress Modal */}
            <FetchProgressModal
                opened={fetchModal.opened}
                onClose={closeFetchModal}
                fetchUrl={fetchModal.url}
                fetchLabel={fetchModal.label}
            />
        </>
    );
}
