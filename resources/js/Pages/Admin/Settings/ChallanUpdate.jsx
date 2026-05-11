
import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import {
    Title,
    Text,
    Stack,
    Box,
    Group,
    Card,
    Badge,
    ActionIcon,
    SimpleGrid,
    Divider,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { IconSearch, IconEdit } from '@tabler/icons-react';
import { AdminLayout } from '../../../Components/Layout';
import { adminNavItems } from '../../../config/navigation';
import {
    ThemedInput,
    ThemedButton,
    ThemedModal,
    ThemedSelect,
} from '../../../Components/UI';
import axios from 'axios';

const statusMap = {
    U: { label: 'Unpaid', color: 'orange' },
    P: { label: 'Paid', color: 'green' },
    B: { label: 'Bounced', color: 'red' },
};

const feeTypeMap = {
    fee: { label: 'Fee', color: 'blue' },
    voucher: { label: 'Voucher', color: 'violet' },
};

export default function ChallanUpdate() {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const [hasSearched, setHasSearched] = useState(false);

    // Metadata for dropdowns
    const [metadata, setMetadata] = useState({
        institutions: [],
        regions: [],
        categories: [],
        heads: [],
        classes: [],
        levels: [],
        sessions: [],
    });

    // Edit Modal State
    const [opened, { open, close }] = useDisclosure(false);
    const [editingChallan, setEditingChallan] = useState(null);

    // Form
    const { data, setData, put, processing, errors, reset } = useForm({
        amount_base: '',
        amount_within_dueDate: '',
        amount_after_dueDate: '',
        amount_arrears: '',
        due_date: '',
        fee_type: '',
        status: '',
        reserved: '',
        year_session_id: '',
        institution_id: '',
        region_id: '',
        fee_fund_category_id: '',
        fee_fund_head_id: '',
        school_class_id: '',
        level_id: '',
    });

    useEffect(() => {
        axios.get('/admin/settings/challan-metadata')
            .then(res => {
                const format = (arr) => arr.map(item => ({
                    value: String(item.id),
                    label: item.label || 'Unknown'
                }));

                setMetadata({
                    institutions: format(res.data.institutions),
                    regions: format(res.data.regions),
                    categories: format(res.data.categories),
                    heads: format(res.data.heads),
                    classes: format(res.data.classes),
                    levels: format(res.data.levels),
                    sessions: format(res.data.sessions),
                });
            })
            .catch(err => console.error("Failed to load metadata", err));
    }, []);

    const handleSearch = (e) => {
        if (e) e.preventDefault();
        setLoading(true);
        setHasSearched(true);
        axios.post('/admin/settings/challan/search', { query: searchQuery })
            .then(res => {
                setSearchResults(res.data.data);
            })
            .catch(err => console.error("Search failed", err))
            .finally(() => setLoading(false));
    };

    const handleEdit = (challan) => {
        setEditingChallan(challan);
        setData({
            amount_base: challan.amount_base || '',
            amount_within_dueDate: challan.amount_within_dueDate || '',
            amount_after_dueDate: challan.amount_after_dueDate || '',
            amount_arrears: challan.amount_arrears || '',
            due_date: challan.due_date ? challan.due_date.substring(0, 10) : '',
            fee_type: challan.fee_type || '',
            status: challan.status || '',
            reserved: challan.reserved || '',
            year_session_id: challan.year_session_id ? String(challan.year_session_id) : '',
            institution_id: challan.institution_id ? String(challan.institution_id) : '',
            region_id: challan.region_id ? String(challan.region_id) : '',
            fee_fund_category_id: challan.fee_fund_category_id ? String(challan.fee_fund_category_id) : '',
            fee_fund_head_id: challan.fee_fund_head_id ? String(challan.fee_fund_head_id) : '',
            school_class_id: challan.school_class_id ? String(challan.school_class_id) : '',
            level_id: challan.level_id ? String(challan.level_id) : '',
        });
        open();
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        put(`/admin/settings/challan/${editingChallan.id}`, {
            onSuccess: () => {
                close();
                handleSearch();
            },
        });
    };

    const getConsumerName = (challan) => {
        const profile = challan.consumer?.profile_details?.[0];
        return profile?.name || 'Unknown';
    };

    const getConsumerNumber = (challan) => {
        return challan.consumer?.consumer_number || '-';
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('en-PK', {
            year: 'numeric', month: 'short', day: 'numeric',
        });
    };

    const formatAmount = (amount) => {
        return parseFloat(amount || 0).toLocaleString('en-PK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Challan Update</Title>
                    <Text c="dimmed" size="sm">
                        Search and manage challans by Consumer ID or Challan Number.
                    </Text>
                </Box>

                {/* Search Box */}
                <Card shadow="sm" padding="lg" radius="md" withBorder>
                    <form onSubmit={handleSearch}>
                        <Group>
                            <ThemedInput
                                placeholder="Search by Consumer Number or Challan No."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                style={{ flex: 1 }}
                                icon={<IconSearch size={16} />}
                            />
                            <ThemedButton type="submit" loading={loading}>
                                Search
                            </ThemedButton>
                        </Group>
                    </form>
                </Card>

                {/* Results */}
                <SimpleGrid cols={{ base: 1, md: 2, lg: 3 }}>
                    {searchResults.map((challan) => {
                        const statusInfo = statusMap[challan.status] || { label: challan.status, color: 'gray' };
                        const feeInfo = feeTypeMap[challan.fee_type] || { label: challan.fee_type, color: 'gray' };

                        return (
                            <Card key={challan.id} shadow="sm" padding="lg" radius="md" withBorder>
                                <Group justify="space-between" mb="xs">
                                    <Group gap="xs">
                                        <Badge color={statusInfo.color} variant="light">
                                            {statusInfo.label}
                                        </Badge>
                                        {challan.status === 'P' && (
                                            <Badge color={challan.sms_sync ? 'green' : 'red'} variant="light" size="xs">
                                                {challan.sms_sync ? 'SMS Synced' : 'SMS Failed'}
                                            </Badge>
                                        )}
                                        <Badge color={feeInfo.color} variant="outline" size="xs">
                                            {feeInfo.label}
                                        </Badge>
                                    </Group>
                                    <ActionIcon variant="subtle" color="blue" onClick={() => handleEdit(challan)}>
                                        <IconEdit size={16} />
                                    </ActionIcon>
                                </Group>

                                <Box mb="sm">
                                    <Text fw={600} size="sm" truncate>{getConsumerName(challan)}</Text>
                                    <Text size="xs" c="dimmed">Consumer: {getConsumerNumber(challan)}</Text>
                                </Box>

                                <Group justify="space-between" align="center" mb="sm">
                                    <Text size="xs" c="dimmed" ff="monospace" truncate>
                                        {challan.challan_no}
                                    </Text>
                                    {challan.year_session && (
                                        <Badge variant="dot" size="xs" color="indigo">
                                            {challan.year_session.name}
                                        </Badge>
                                    )}
                                </Group>

                                <Divider mb="sm" />

                                <Stack gap={4}>
                                    <Group justify="space-between">
                                        <Text size="xs" c="dimmed">Base Amount</Text>
                                        <Text size="sm" fw={500}>Rs. {formatAmount(challan.amount_base)}</Text>
                                    </Group>
                                    <Group justify="space-between">
                                        <Text size="xs" c="dimmed">Arrears</Text>
                                        <Text size="sm">Rs. {formatAmount(challan.amount_arrears)}</Text>
                                    </Group>
                                    <Group justify="space-between">
                                        <Text size="xs" c="dimmed">Total (Within Due)</Text>
                                        <Text size="sm" fw={600} color="blue">Rs. {formatAmount(challan.amount_within_dueDate)}</Text>
                                    </Group>
                                    <Group justify="space-between">
                                        <Text size="xs" c="dimmed">After Due Date</Text>
                                        <Text size="sm">Rs. {formatAmount(challan.amount_after_dueDate)}</Text>
                                    </Group>
                                    <Group justify="space-between">
                                        <Text size="xs" c="dimmed">Due Date</Text>
                                        <Text size="sm">{formatDate(challan.due_date)}</Text>
                                    </Group>
                                    {challan.date_paid && (
                                        <Group justify="space-between">
                                            <Text size="xs" c="dimmed">Date Paid</Text>
                                            <Text size="sm" c="green">{formatDate(challan.date_paid)}</Text>
                                        </Group>
                                    )}
                                </Stack>

                                {challan.reserved && (
                                    <>
                                        <Divider my="xs" />
                                        <Text size="xs" c="dimmed" lineClamp={2}>
                                            {challan.reserved}
                                        </Text>
                                    </>
                                )}
                            </Card>
                        );
                    })}
                </SimpleGrid>

                {searchResults.length === 0 && hasSearched && !loading && (
                    <Text c="dimmed" ta="center" mt="lg">
                        No challans found. Try a different search term.
                    </Text>
                )}

                {/* Edit Modal */}
                <ThemedModal
                    opened={opened}
                    onClose={() => { close(); reset(); }}
                    title="Edit Challan Details"
                    centered
                    size="xl"
                >
                    <form onSubmit={handleUpdate}>
                        <Stack>
                            {editingChallan && (
                                <Box
                                    p="sm"
                                    style={{
                                        borderRadius: 8,
                                        background: 'var(--mantine-color-dark-6, #f8f9fa)',
                                    }}
                                >
                                    <Text size="xs" c="dimmed">Challan No</Text>
                                    <Text size="sm" ff="monospace" fw={500}>
                                        {editingChallan.challan_no}
                                    </Text>
                                    <Text size="xs" c="dimmed" mt="xs">Consumer</Text>
                                    <Text size="sm">
                                        {getConsumerName(editingChallan)} ({getConsumerNumber(editingChallan)})
                                    </Text>
                                </Box>
                            )}

                            <SimpleGrid cols={2}>
                                <ThemedInput
                                    label="Base Amount"
                                    type="number"
                                    step="0.01"
                                    value={data.amount_base}
                                    onChange={e => setData('amount_base', e.target.value)}
                                    error={errors.amount_base}
                                />
                                <ThemedInput
                                    label="Arrears"
                                    type="number"
                                    step="0.01"
                                    value={data.amount_arrears}
                                    onChange={e => setData('amount_arrears', e.target.value)}
                                    error={errors.amount_arrears}
                                />
                                <ThemedInput
                                    label="Amount Within Due Date"
                                    type="number"
                                    step="0.01"
                                    value={data.amount_within_dueDate}
                                    onChange={e => setData('amount_within_dueDate', e.target.value)}
                                    error={errors.amount_within_dueDate}
                                />
                                <ThemedInput
                                    label="Amount After Due Date"
                                    type="number"
                                    step="0.01"
                                    value={data.amount_after_dueDate}
                                    onChange={e => setData('amount_after_dueDate', e.target.value)}
                                    error={errors.amount_after_dueDate}
                                />
                            </SimpleGrid>

                            <SimpleGrid cols={3}>
                                <ThemedInput
                                    label="Due Date"
                                    type="date"
                                    value={data.due_date}
                                    onChange={e => setData('due_date', e.target.value)}
                                    error={errors.due_date}
                                />
                                <ThemedSelect
                                    label="Status"
                                    data={[
                                        { value: 'U', label: 'Unpaid' },
                                        { value: 'P', label: 'Paid' },
                                        { value: 'B', label: 'Bounced' },
                                    ]}
                                    value={data.status}
                                    onChange={(value) => setData('status', value)}
                                    error={errors.status}
                                />
                                <ThemedSelect
                                    label="Fee Type"
                                    data={[
                                        { value: 'fee', label: 'Fee' },
                                        { value: 'voucher', label: 'Voucher' },
                                    ]}
                                    value={data.fee_type}
                                    onChange={(value) => setData('fee_type', value)}
                                    error={errors.fee_type}
                                />
                            </SimpleGrid>

                            <Divider label="Linking & Structure" labelPosition="center" />

                            <SimpleGrid cols={2}>
                                <ThemedSelect
                                    label="Institution"
                                    placeholder="Search institution"
                                    data={metadata.institutions}
                                    value={data.institution_id}
                                    onChange={(value) => setData('institution_id', value)}
                                    error={errors.institution_id}
                                    searchable
                                    clearable
                                />
                                <ThemedSelect
                                    label="Year Session"
                                    placeholder="Select session"
                                    data={metadata.sessions}
                                    value={data.year_session_id}
                                    onChange={(value) => setData('year_session_id', value)}
                                    error={errors.year_session_id}
                                    searchable
                                    clearable
                                />
                            </SimpleGrid>

                            <SimpleGrid cols={2}>
                                <ThemedSelect
                                    label="Region"
                                    placeholder="Select region"
                                    data={metadata.regions}
                                    value={data.region_id}
                                    onChange={(value) => setData('region_id', value)}
                                    error={errors.region_id}
                                    searchable
                                    clearable
                                />
                                <ThemedSelect
                                    label="Level"
                                    placeholder="Select level"
                                    data={metadata.levels}
                                    value={data.level_id}
                                    onChange={(value) => setData('level_id', value)}
                                    error={errors.level_id}
                                    searchable
                                    clearable
                                />
                            </SimpleGrid>

                            <SimpleGrid cols={3}>
                                <ThemedSelect
                                    label="Class / Section"
                                    placeholder="Select class"
                                    data={metadata.classes}
                                    value={data.school_class_id}
                                    onChange={(value) => setData('school_class_id', value)}
                                    error={errors.school_class_id}
                                    searchable
                                    clearable
                                />
                                <ThemedSelect
                                    label="Fee Category"
                                    placeholder="Select category"
                                    data={metadata.categories}
                                    value={data.fee_fund_category_id}
                                    onChange={(value) => setData('fee_fund_category_id', value)}
                                    error={errors.fee_fund_category_id}
                                    searchable
                                    clearable
                                />
                                <ThemedSelect
                                    label="Fee Head"
                                    placeholder="Select head"
                                    data={metadata.heads}
                                    value={data.fee_fund_head_id}
                                    onChange={(value) => setData('fee_fund_head_id', value)}
                                    error={errors.fee_fund_head_id}
                                    searchable
                                    clearable
                                />
                            </SimpleGrid>

                            <ThemedInput
                                label="Remarks"
                                value={data.reserved}
                                onChange={e => setData('reserved', e.target.value)}
                                error={errors.reserved}
                            />

                            <Group justify="flex-end" mt="md">
                                <ThemedButton themeVariant="subtle" onClick={close}>Cancel</ThemedButton>
                                <ThemedButton type="submit" loading={processing}>Update Challan</ThemedButton>
                            </Group>
                        </Stack>
                    </form>
                </ThemedModal>
            </Stack>
        </AdminLayout>
    );
}
