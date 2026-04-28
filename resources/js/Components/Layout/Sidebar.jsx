import { useState } from 'react';
import { AppShell, NavLink, ScrollArea, Stack, Text, Box, Tooltip, ActionIcon, Group } from '@mantine/core';
import {
    IconMap,
    IconStairs,
    IconBuilding,
    IconLayoutDashboard,
    IconUsers,
    IconReceipt,
    IconCoin,
    IconChartBar,
    IconSettings,
    IconDatabase,
    IconList,
    IconCalendar
} from '@tabler/icons-react';
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
    dashboard: () => <IconLayoutDashboard size={20} stroke={1.5} />,
    users: () => <IconUsers size={20} stroke={1.5} />,
    receipt: () => <IconReceipt size={20} stroke={1.5} />,
    currency: () => <IconCoin size={20} stroke={1.5} />,
    chart: () => <IconChartBar size={20} stroke={1.5} />,
    settings: () => <IconSettings size={20} stroke={1.5} />,
    tool: () => <IconDatabase size={20} stroke={1.5} />,
    map: () => <IconMap size={20} stroke={1.5} />,
    stairs: () => <IconStairs size={20} stroke={1.5} />,
    building: () => <IconBuilding size={20} stroke={1.5} />,
    list: () => <IconList size={20} stroke={1.5} />,
    calendar: () => <IconCalendar size={20} stroke={1.5} />,
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
    const [fetchModal, setFetchModal] = useState({ opened: false, url: '', label: '', method: 'GET' });

    const openFetchModal = (href, label, method = 'GET') => {
        const cleanLabel = label.replace('→ ', '').replace('Fetch ', '');
        setFetchModal({ opened: true, url: href, label: cleanLabel, method });
    };

    const closeFetchModal = () => {
        setFetchModal({ opened: false, url: '', label: '', method: 'GET' });
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
                            const isActionItem = child.action === 'fetch' || child.action === 'generate' || child.action === 'archive';
                            return (
                                <NavLink
                                    key={child.href}
                                    component={isActionItem ? 'button' : Link}
                                    href={isActionItem ? undefined : child.href}
                                    label={child.label}
                                    active={isActive(child.href)}
                                    onClick={() => {
                                        closeMobile();
                                        if (isActionItem) {
                                            const method = (child.action === 'generate' || child.action === 'archive') ? 'POST' : 'GET';
                                            openFetchModal(child.href, child.label, method);
                                        }
                                    }}
                                    style={{
                                        borderRadius: 8,
                                        fontSize: '0.9em',
                                        fontWeight: isActive(child.href) ? 600 : undefined,
                                        cursor: isActionItem ? 'pointer' : undefined,
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
                fetchMethod={fetchModal.method}
            />
        </>
    );
}
