import React, { useState, useEffect } from 'react';
import {
    Container,
    Paper,
    Title,
    Text,
    Button,
    Stack,
    Group,
    Stepper,
    Alert,
    ThemeIcon,
    Box,
    Badge,
    Divider,
    Code,
    List,
    SimpleGrid
} from '@mantine/core';
import {
    IconArchive,
    IconUsers,
    IconFilePlus,
    IconCheck,
    IconAlertCircle,
    IconCalendarMonth,
    IconArrowRight,
    IconHistory,
    IconRestore
} from '@tabler/icons-react';
import { AdminLayout } from '@/Components/Layout';
import { useTheme } from '@/theme';
import { adminNavItems } from '@/config/navigation';
import { DataTable } from '@/Components/DataTable';
import axios from 'axios';
import { notifications } from '@mantine/notifications';
import FetchProgressModal from '@/Components/UI/FetchProgressModal';

export default function MonthlyProcedure() {
    const { primaryColor } = useTheme();
    const [active, setActive] = useState(0);
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState({
        step1: null,
        step2: null,
        step3: null
    });
    const [snapshots, setSnapshots] = useState({});
    const [history, setHistory] = useState({ data: [], current_page: 1, last_page: 1 });
    const [progressModal, setProgressModal] = useState({
        opened: false,
        url: '',
        label: '',
        method: 'GET'
    });

    useEffect(() => {
        fetchSnapshots();
        fetchHistory();
    }, []);

    const fetchSnapshots = async () => {
        try {
            const res = await axios.get('/admin/security-audit/latest-snapshots');
            setSnapshots(res.data);
        } catch (err) {
            console.error('Failed to fetch snapshots', err);
        }
    };

    const fetchHistory = async (page = 1) => {
        try {
            const res = await axios.get(`/admin/security-audit/snapshot-history?page=${page}`);
            setHistory(res.data);
        } catch (err) {
            console.error('Failed to fetch history', err);
        }
    };

    const runRollback = async (snapshotId) => {
        if (!snapshotId) return;

        setLoading(true);
        try {
            const res = await axios.post(`/admin/procedure/rollback/${snapshotId}`);
            notifications.show({
                title: 'Rollback Success',
                message: res.data.message,
                color: 'blue'
            });
            fetchSnapshots();
            fetchHistory();
        } catch (err) {
            notifications.show({
                title: 'Rollback Failed',
                message: err.response?.data?.message || 'Failed to rollback.',
                color: 'red'
            });
        } finally {
            setLoading(false);
        }
    };

    const runStep1 = () => {
        setProgressModal({
            opened: true,
            url: '/admin/utilities/archiveChallans',
            label: 'Archive History',
            method: 'POST'
        });
    };

    const runStep2 = () => {
        setProgressModal({
            opened: true,
            url: '/admin/api/fetch/student',
            label: 'Student Sync',
            method: 'GET'
        });
    };

    const runStep3 = () => {
        setProgressModal({
            opened: true,
            url: '/admin/utilities/generateChallans',
            label: 'Bulk Generation',
            method: 'POST'
        });
    };

    const onModalClose = () => {
        setProgressModal(prev => ({ ...prev, opened: false }));
        fetchSnapshots();
        fetchHistory();
    };

    // Calculate active step
    let activeStep = 0;
    if (snapshots.archive && !snapshots.archive.is_rolled_back) {
        activeStep = 1;
        if (snapshots.sync && !snapshots.sync.is_rolled_back && new Date(snapshots.sync.created_at) > new Date(snapshots.archive.created_at)) {
            activeStep = 2;
            if (snapshots.generate && !snapshots.generate.is_rolled_back && new Date(snapshots.generate.created_at) > new Date(snapshots.sync.created_at)) {
                activeStep = 3;
            }
        }
    }

    return (
        <AdminLayout title="Monthly Procedure" navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Monthly Lifecycle Management</Title>
                    <Text c="dimmed" size="sm">
                        Follow these steps at the turn of each month to maintain financial accuracy and generate new billing records.
                    </Text>
                </Box>

                <Paper p="xl" radius="md" withBorder shadow="sm" mb="md">
                    <Stepper active={activeStep} allowNextStepsSelect={false}>
                        <Stepper.Step label="Archive History" description="Moves unpaid challans to history" />
                        <Stepper.Step label="Sync Students" description="Updates profiles and fees" />
                        <Stepper.Step label="Generate Challans" description="Calculates new fees and arrears" />
                        <Stepper.Completed>
                            <Text fw={500} ta="center" c="green">Monthly procedure complete! All challans generated.</Text>
                        </Stepper.Completed>
                    </Stepper>
                </Paper>

                <SimpleGrid cols={{ base: 1, md: 3 }} spacing="lg">
                    {/* Step 1 Card */}
                    <Paper p="xl" radius="md" withBorder shadow="sm">
                        <Stack h="100%" justify="space-between">
                            <Box>
                                <Group justify="space-between" mb="md">
                                    <ThemeIcon size={44} radius="md" color="blue" variant="light">
                                        <IconArchive size={26} />
                                    </ThemeIcon>
                                    <Badge variant="outline">STEP 1</Badge>
                                </Group>
                                <Title order={4} mb="xs">Archive History</Title>
                                <Text size="sm" c="dimmed" mb="md">
                                    Move currently unpaid challans to history. Perform around the <strong>27th</strong>.
                                </Text>
                                <Alert icon={<IconAlertCircle size={16} />} color="blue" variant="light" mb="md">
                                    Required before new arrears calculation.
                                </Alert>
                            </Box>
                            <Stack gap="xs">
                                <Button
                                    fullWidth
                                    leftSection={<IconArchive size={16} />}
                                    onClick={runStep1}
                                    loading={loading}
                                    color="blue"
                                >
                                    Execute Archive
                                </Button>
                                {snapshots.archive && !snapshots.archive.is_rolled_back && (
                                    <Button
                                        fullWidth
                                        variant="subtle"
                                        color="red"
                                        size="xs"
                                        onClick={() => runRollback(snapshots.archive.id)}
                                        loading={loading}
                                        leftSection={<IconHistory size={14} />}
                                    >
                                        Rollback Step 1
                                    </Button>
                                )}
                            </Stack>
                        </Stack>
                    </Paper>

                    {/* Step 2 Card */}
                    <Paper p="xl" radius="md" withBorder shadow="sm">
                        <Stack h="100%" justify="space-between">
                            <Box>
                                <Group justify="space-between" mb="md">
                                    <ThemeIcon size={44} radius="md" color="cyan" variant="light">
                                        <IconUsers size={26} />
                                    </ThemeIcon>
                                    <Badge variant="outline">STEP 2</Badge>
                                </Group>
                                <Title order={4} mb="xs">Sync Students</Title>
                                <Text size="sm" c="dimmed" mb="md">
                                    Fetch latest data from SIS API. Perform on the <strong>1st</strong>.
                                </Text>
                                <Alert icon={<IconAlertCircle size={16} />} color="cyan" variant="light" mb="md">
                                    Updates profiles and fee categories.
                                </Alert>
                                {results.step2 && (
                                    <List size="xs" spacing="xs" mb="md" withPadding>
                                        <List.Item>New: <Badge size="xs" color="green">{results.step2.stats.inserted}</Badge></List.Item>
                                        <List.Item>Updated: <Badge size="xs" color="blue">{results.step2.stats.updated}</Badge></List.Item>
                                    </List>
                                )}
                            </Box>
                            <Stack gap="xs">
                                <Button
                                    fullWidth
                                    leftSection={<IconUsers size={16} />}
                                    onClick={runStep2}
                                    loading={loading}
                                    color="cyan"
                                >
                                    Sync Data
                                </Button>
                                {snapshots.sync && !snapshots.sync.is_rolled_back && (
                                    <Button
                                        fullWidth
                                        variant="subtle"
                                        color="red"
                                        size="xs"
                                        onClick={() => runRollback(snapshots.sync.id)}
                                        loading={loading}
                                        leftSection={<IconHistory size={14} />}
                                    >
                                        Rollback Step 2
                                    </Button>
                                )}
                            </Stack>
                        </Stack>
                    </Paper>

                    {/* Step 3 Card */}
                    <Paper p="xl" radius="md" withBorder shadow="sm">
                        <Stack h="100%" justify="space-between">
                            <Box>
                                <Group justify="space-between" mb="md">
                                    <ThemeIcon size={44} radius="md" color="green" variant="light">
                                        <IconFilePlus size={26} />
                                    </ThemeIcon>
                                    <Badge variant="outline">STEP 3</Badge>
                                </Group>
                                <Title order={4} mb="xs">Generate Challans</Title>
                                <Text size="sm" c="dimmed" mb="md">
                                    Create new billing records. Perform on <strong>1st working day</strong>.
                                </Text>
                                <Alert icon={<IconAlertCircle size={16} />} color="green" variant="light" mb="md">
                                    Calculates current fees + arrears.
                                </Alert>
                            </Box>
                            <Stack gap="xs">
                                <Button
                                    fullWidth
                                    leftSection={<IconFilePlus size={16} />}
                                    onClick={runStep3}
                                    loading={loading}
                                    color="green"
                                >
                                    Generate Bulk
                                </Button>
                                {snapshots.generate && !snapshots.generate.is_rolled_back && (
                                    <Button
                                        fullWidth
                                        variant="subtle"
                                        color="red"
                                        size="xs"
                                        onClick={() => runRollback(snapshots.generate.id)}
                                        loading={loading}
                                        leftSection={<IconHistory size={14} />}
                                    >
                                        Rollback Step 3
                                    </Button>
                                )}
                            </Stack>
                        </Stack>
                    </Paper>
                </SimpleGrid>

                <Paper p="xl" radius="md" withBorder style={{ borderStyle: 'dashed' }}>
                    <Stack gap="xs">
                        <Title order={4}>Procedure Guidelines</Title>
                        <Text size="sm">1. <strong>Archiving</strong>: Ensure all bank data for the previous month is fully synced before archiving.</Text>
                        <Text size="sm">2. <strong>Syncing</strong>: SIS sync must be complete to ensure students are assigned to their current classes.</Text>
                        <Text size="sm">3. <strong>Generation</strong>: Arrears calculation relies on the "latest" historical record; never skip Step 1.</Text>
                    </Stack>
                </Paper>

                {/* History Table */}
                <Box mt="xl">
                    <Title order={3} mb="md">Procedure Execution History</Title>
                    <DataTable
                        columns={[
                            {
                                key: 'step_name',
                                label: 'Procedure Stage',
                                render: (val) => (
                                    <Badge variant="light" color={val === 'archive' ? 'blue' : val === 'sync' ? 'cyan' : 'green'}>
                                        {val.toUpperCase()}
                                    </Badge>
                                )
                            },
                            {
                                key: 'created_at',
                                label: 'Execution Time',
                                render: (val) => new Date(val).toLocaleString()
                            },
                            {
                                key: 'is_rolled_back',
                                label: 'Status',
                                render: (val) => val ? (
                                    <Badge color="red" variant="dot">Rolled Back</Badge>
                                ) : (
                                    <Badge color="green" variant="dot">Active</Badge>
                                )
                            },
                            {
                                key: 'actions',
                                label: 'Actions',
                                render: (val, row) => !row.is_rolled_back && (
                                    <Button
                                        variant="subtle"
                                        color="red"
                                        size="xs"
                                        leftSection={<IconRestore size={14} />}
                                        onClick={() => runRollback(row.id)}
                                        loading={loading}
                                    >
                                        Rollback
                                    </Button>
                                )
                            }
                        ]}
                        data={history.data}
                        pagination={history}
                        onPageChange={fetchHistory}
                        showActions={false}
                        title="Recent Snapshots"
                    />
                </Box>
            </Stack>

            <FetchProgressModal
                opened={progressModal.opened}
                onClose={onModalClose}
                fetchUrl={progressModal.url}
                fetchLabel={progressModal.label}
                fetchMethod={progressModal.method}
            />
        </AdminLayout>
    );
}
