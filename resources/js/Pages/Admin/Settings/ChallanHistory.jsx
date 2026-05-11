
import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Title,
    Text,
    Stack,
    Box,
    Group,
    Badge,
    SimpleGrid,
    Divider,
    Tabs,
    ActionIcon,
    Tooltip,
    Code,
    ScrollArea,
} from '@mantine/core';
import { IconHistory, IconFileCheck, IconEye } from '@tabler/icons-react';
import { AdminLayout } from '../../../Components/Layout';
import { DataTable } from '../../../Components/DataTable';
import { adminNavItems } from '../../../config/navigation';
import { ThemedModal, ThemedButton } from '../../../Components/UI';

const statusMap = {
    U: { label: 'Unpaid', color: 'orange' },
    P: { label: 'Paid', color: 'green' },
    B: { label: 'Bounced', color: 'red' },
};

export default function ChallanHistory({ activeChallans, archivedChallans, filters }) {
    const [activeTab, setActiveTab] = useState('active');
    const [snapshotModal, setSnapshotModal] = useState({ opened: false, data: null, row: null });
    const [isRetryingSync, setIsRetryingSync] = useState(false);

    const handleRetrySync = () => {
        setIsRetryingSync(true);
        router.post('/admin/settings/retry-sms-sync', {}, {
            onFinish: () => setIsRetryingSync(false),
            preserveScroll: true
        });
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return 'N/A';
        return new Date(dateStr).toLocaleDateString('en-PK', {
            year: 'numeric', month: 'short', day: 'numeric',
        });
    };

    const openSnapshot = (challan) => {
        try {
            const snapshot = typeof challan.challan_snapshot === 'string'
                ? JSON.parse(challan.challan_snapshot)
                : challan.challan_snapshot;
            setSnapshotModal({ opened: true, data: snapshot, row: challan });
        } catch (e) {
            console.error("Failed to parse snapshot", e);
            setSnapshotModal({ opened: true, data: { error: "Invalid snapshot data" }, row: challan });
        }
    };

    const columns = [
        { key: 'challan_no', label: 'Challan No', width: 180 },
        {
            key: 'consumer',
            label: 'Consumer',
            render: (val, row) => {
                const profile = row.consumer?.profile_details?.[0];
                return (
                    <Box>
                        <Text size="sm" fw={500}>{profile?.name || 'N/A'}</Text>
                        <Text size="xs" c="dimmed">{row.consumer?.consumer_number || row.consumer_id || '-'}</Text>
                    </Box>
                );
            }
        },
        {
            key: 'amount_within_dueDate',
            label: 'Amount',
            render: (val) => `Rs. ${parseFloat(val || 0).toLocaleString()}`
        },
        {
            key: 'due_date',
            label: 'Due Date',
            render: (val) => val ? new Date(val).toLocaleDateString() : '-'
        },
        {
            key: 'status',
            label: 'Status',
            render: (val) => {
                const info = statusMap[val] || { label: val, color: 'gray' };
                return <Badge color={info.color} size="sm">{info.label}</Badge>;
            }
        },
        {
            key: 'sms_sync',
            label: 'SMS Sync',
            render: (val, row) => {
                if (row.status !== 'P') return <Text size="sm" c="dimmed">-</Text>;
                return val ? (
                    <Badge color="green" size="sm" variant="light">Synced</Badge>
                ) : (
                    <Badge color="red" size="sm" variant="light">Failed</Badge>
                );
            }
        },
        {
            key: 'challan_snapshot',
            label: 'Snapshot',
            render: (val, row) => (
                <ActionIcon variant="light" color="blue" onClick={() => openSnapshot(row)}>
                    <IconEye size={16} />
                </ActionIcon>
            )
        }
    ];

    const archivedColumns = [
        { key: 'challan_no', label: 'Challan No', width: 180 },
        {
            key: 'amount_within_dueDate',
            label: 'Amount (Archived)',
            render: (val) => `Rs. ${parseFloat(val || 0).toLocaleString()}`
        },
        {
            key: 'due_date',
            label: 'Due Date',
            render: (val) => val ? new Date(val).toLocaleDateString() : '-'
        },
        {
            key: 'status',
            label: 'Status',
            render: (val) => {
                const info = statusMap[val] || { label: val, color: 'gray' };
                return <Badge color={info.color} size="sm">{info.label}</Badge>;
            }
        },
        {
            key: 'created_at',
            label: 'Archived On',
            render: (val) => new Date(val).toLocaleString()
        },
        {
            key: 'sms_sync',
            label: 'SMS Sync',
            render: (val, row) => {
                if (row.status !== 'P') return <Text size="sm" c="dimmed">-</Text>;
                return val ? (
                    <Badge color="green" size="sm" variant="light">Synced</Badge>
                ) : (
                    <Badge color="red" size="sm" variant="light">Failed</Badge>
                );
            }
        },
        {
            key: 'challan_snapshot',
            label: 'Snapshot',
            render: (val, row) => (
                <ActionIcon variant="light" color="blue" onClick={() => openSnapshot(row)}>
                    <IconEye size={16} />
                </ActionIcon>
            )
        }
    ];

    const handlePageChange = (page, type) => {
        const params = { ...filters };
        if (type === 'active') params.active_page = page;
        if (type === 'archived') params.archived_page = page;

        router.get('/admin/settings/challan-history', params, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleSearch = (val) => {
        router.get('/admin/settings/challan-history', { ...filters, search: val, active_page: 1, archived_page: 1 }, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Head title="Challan History" />

            <Stack gap="lg">
                <Group justify="space-between" align="flex-start">
                    <Box>
                        <Title order={2} mb={4}>Challan Dashboard</Title>
                        <Text c="dimmed" size="sm">
                            View current active challans and historical archived records.
                        </Text>
                    </Box>
                    <ThemedButton
                        onClick={handleRetrySync}
                        loading={isRetryingSync}
                    >
                        Retry Failed SMS Sync
                    </ThemedButton>
                </Group>

                <Tabs value={activeTab} onChange={setActiveTab} variant="outline" radius="md">
                    <Tabs.List>
                        <Tabs.Tab value="active" leftSection={<IconFileCheck size={16} />}>
                            Active Challans ({activeChallans.total})
                        </Tabs.Tab>
                        <Tabs.Tab value="archived" leftSection={<IconHistory size={16} />}>
                            History ({archivedChallans.total})
                        </Tabs.Tab>
                    </Tabs.List>

                    <Tabs.Panel value="active" pt="md">
                        <DataTable
                            title="Current Session Challans"
                            columns={columns}
                            data={activeChallans.data}
                            pagination={activeChallans}
                            onPageChange={(p) => handlePageChange(p, 'active')}
                            onSearch={handleSearch}
                            showActions={false}
                        />
                    </Tabs.Panel>

                    <Tabs.Panel value="archived" pt="md">
                        <DataTable
                            title="Archived Challans"
                            columns={archivedColumns}
                            data={archivedChallans.data}
                            pagination={archivedChallans}
                            onPageChange={(p) => handlePageChange(p, 'archived')}
                            onSearch={handleSearch}
                            showActions={false}
                        />
                    </Tabs.Panel>
                </Tabs>
            </Stack>

            <ThemedModal
                opened={snapshotModal.opened}
                onClose={() => setSnapshotModal({ opened: false, data: null })}
            title="Challan Snapshot Details"
            size="xl"
            centered
        >
            <ScrollArea h={600} type="always" offsetScrollbars>
                {snapshotModal.data ? (
                    <Stack gap="xl">
                        {/* Summary Section */}
                        <Box>
                            <Text fw={700} size="md" mb="xs" c="blue">Record Summary (Current Mapping)</Text>
                            <SimpleGrid cols={2} spacing="xs">
                                <Box>
                                    <Text size="xs" c="dimmed">Challan No</Text>
                                    <Text size="sm" fw={500}>{snapshotModal.row?.challan_no}</Text>
                                </Box>
                                <Box>
                                    <Text size="xs" c="dimmed">Status</Text>
                                    <Group gap="xs">
                                        <Badge size="sm" color={statusMap[snapshotModal.row?.status]?.color || 'gray'}>
                                            {statusMap[snapshotModal.row?.status]?.label || snapshotModal.row?.status}
                                        </Badge>
                                        {snapshotModal.row?.status === 'P' && (
                                            <Badge size="sm" color={snapshotModal.row?.sms_sync ? 'green' : 'red'} variant="light">
                                                {snapshotModal.row?.sms_sync ? 'SMS Synced' : 'SMS Failed'}
                                            </Badge>
                                        )}
                                    </Group>
                                </Box>
                                <Box>
                                    <Text size="xs" c="dimmed">Institution (ID: {snapshotModal.row?.institution_id || 'N/A'})</Text>
                                    <Text size="sm">{snapshotModal.row?.institution?.name || 'N/A'}</Text>
                                </Box>
                                <Box>
                                    <Text size="xs" c="dimmed">Region (ID: {snapshotModal.row?.region_id || 'N/A'})</Text>
                                    <Text size="sm">{snapshotModal.row?.region?.name || 'N/A'}</Text>
                                </Box>
                                <Box>
                                    <Text size="xs" c="dimmed">Year Session (ID: {snapshotModal.row?.year_session_id || 'N/A'})</Text>
                                    <Text size="sm">{snapshotModal.row?.year_session?.name || 'N/A'}</Text>
                                </Box>
                                <Box style={{ gridColumn: 'span 2' }}>
                                    <Text size="xs" c="dimmed" mb={4}>Fee Breakdown (Current Snapshot)</Text>
                                    <Stack gap={4}>
                                        {snapshotModal.data?.fee_structures?.[0]?.fee_head_amounts ? (
                                            Object.entries(snapshotModal.data.fee_structures[0].fee_head_amounts).map(([head, amount], idx) => (
                                                <Group key={idx} justify="space-between">
                                                    <Text size="xs">{head}</Text>
                                                    <Text size="xs" fw={500}>Rs. {parseFloat(amount || 0).toLocaleString()}</Text>
                                                </Group>
                                            ))
                                        ) : (
                                            <Text size="xs" c="dimmed">No breakdown available in snapshot.</Text>
                                        )}
                                    </Stack>
                                </Box>

                                {snapshotModal.data?.arrears_calculation?.details?.length > 0 && (
                                    <Box style={{ gridColumn: 'span 2' }}>
                                        <Divider label="Arrears Breakdown (Previous Unpaid)" labelPosition="center" my="sm" />
                                        <Stack gap="md">
                                            {snapshotModal.data.arrears_calculation.details.map((arrear, idx) => (
                                                <Box key={idx} p="xs" style={{ background: '#fff5f5', borderRadius: '4px', border: '1px solid #ffc9c9' }}>
                                                    <Group justify="space-between" mb={4}>
                                                        <Text size="xs" fw={700} c="red.9">Challan #{arrear.challan_no} ({arrear.source})</Text>
                                                        <Text size="xs" fw={700} c="red.9">Rs. {parseFloat(arrear.amount || 0).toLocaleString()}</Text>
                                                    </Group>
                                                    <Stack gap={2}>
                                                        {arrear.breakdown?.[0]?.fee_head_amounts ? (
                                                            Object.entries(arrear.breakdown[0].fee_head_amounts).map(([head, amt], sidx) => (
                                                                <Group key={sidx} justify="space-between">
                                                                    <Text size="10px" c="dimmed">{head}</Text>
                                                                    <Text size="10px">Rs. {parseFloat(amt || 0).toLocaleString()}</Text>
                                                                </Group>
                                                            ))
                                                        ) : (
                                                            <Text size="10px" c="dimmed">No detailed breakdown captured.</Text>
                                                        )}
                                                    </Stack>
                                                </Box>
                                            ))}
                                        </Stack>
                                    </Box>
                                )}
                            </SimpleGrid>
                        </Box>

                        <Divider label="Raw Snapshot Data (JSON)" labelPosition="center" />

                        <Box>
                            <Text size="xs" c="dimmed" mb="xs">This is the point-in-time data captured when the challan was generated or archived.</Text>
                            <Code block color="blue.0" p="md" style={{ border: '1px solid #e9ecef', fontSize: '11px' }}>
                                {JSON.stringify(snapshotModal.data, null, 4)}
                            </Code>
                        </Box>
                    </Stack>
                ) : (
                    <Text ta="center" py="xl" c="dimmed">No snapshot data available.</Text>
                )}
            </ScrollArea>
                <Group justify="flex-end" mt="md">
                    <ThemedButton onClick={() => setSnapshotModal({ opened: false, data: null })}>Close</ThemedButton>
                </Group>
            </ThemedModal>
        </AdminLayout>
    );
}
