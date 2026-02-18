import {
    Title,
    Text,
    SimpleGrid,
    Card,
    Group,
    Stack,
    Box,
} from '@mantine/core';
import { AdminLayout } from '../../Components/Layout';
import { useTheme } from '../../theme';
import { adminNavItems } from '../../config/navigation';

// Stat icons - will be colored by theme
const UsersIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </svg>
);

const ReceiptIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>
    </svg>
);

const CurrencyIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/>
    </svg>
);

const TrendUpIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
    </svg>
);

const CheckIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M20 6 9 17l-5-5"/>
    </svg>
);

const XIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
    </svg>
);

const BanIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
       <circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/>
   </svg>
);

export default function Dashboard({ stats }) {
    const { ui, colorConfig } = useTheme();

    const statData = [
        { title: 'Total Consumers', value: stats.total_consumers?.toLocaleString(), icon: UsersIcon },
        { title: 'Active Challans', value: stats.active_challans?.toLocaleString(), icon: ReceiptIcon },
        { title: 'Paid Challans', value: stats.paid_challans?.toLocaleString(), icon: CheckIcon },
        { title: 'Unpaid Challans', value: stats.unpaid_challans?.toLocaleString(), icon: XIcon },
        { title: 'Blocked Challans', value: stats.blocked_challans?.toLocaleString(), icon: BanIcon },
        { title: 'Total Collection', value: `₨ ${stats.total_collection?.toLocaleString()}`, icon: CurrencyIcon },
    ];

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                {/* Page Header */}
                <Box>
                    <Title order={2} mb={4}>Dashboard</Title>
                    <Text c="dimmed" size="sm">
                        Welcome back! Here's what's happening with your system.
                    </Text>
                </Box>

                {/* Stats Grid - icons colored by theme, containers neutral */}
                <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="lg">
                    {statData.map((stat, index) => (
                        <Card
                            key={stat.title}
                            shadow="sm"
                            radius="md"
                            padding="lg"
                            style={{
                                background: ui.cardBg,
                                border: `1px solid ${ui.border}`,
                            }}
                        >
                            <Box mb="md" style={{ color: colorConfig.primary }}>
                                <stat.icon />
                            </Box>

                            <Text size="1.5rem" fw={700} style={{ lineHeight: 1 }}>
                                {stat.value}
                            </Text>
                            <Text size="sm" c="dimmed" mt="xs">
                                {stat.title}
                            </Text>
                        </Card>
                    ))}
                </SimpleGrid>
            </Stack>
        </AdminLayout>
    );
}
