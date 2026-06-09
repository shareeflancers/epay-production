import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Container,
    Title,
    Paper,
    TextInput,
    Button,
    Group,
    Text,
    Card,
    Badge,
    Grid,
    Alert,
    Box,
    ActionIcon,
} from '@mantine/core';
import { IconSearch, IconAlertCircle, IconCheck, IconUser, IconBuilding, IconId } from '@tabler/icons-react';
import AdminLayout from '../../../Components/Layout/AdminLayout';
import { adminNavItems } from '../../../config/navigation';
import axios from 'axios';

export default function ConsumerUpdate() {
    const [searchQuery, setSearchQuery] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [searchPerformed, setSearchPerformed] = useState(false);
    
    // For editing
    const [editingConsumer, setEditingConsumer] = useState(null);

    const { data, setData, put, processing, errors, reset, clearErrors } = useForm({
        identification_number: '',
    });

    const handleSearch = async (e) => {
        e.preventDefault();
        if (!searchQuery.trim()) return;

        setIsSearching(true);
        setSearchPerformed(true);
        setEditingConsumer(null);
        clearErrors();

        try {
            const response = await axios.post('/admin/settings/consumer-update/search', { query: searchQuery });
            setSearchResults(response.data.data);
        } catch (error) {
            console.error('Search failed:', error);
        } finally {
            setIsSearching(false);
        }
    };

    const startEditing = (consumer) => {
        setEditingConsumer(consumer);
        setData({
            identification_number: consumer.identification_number || '',
        });
        clearErrors();
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editingConsumer) return;

        put(`/admin/settings/consumer-update/${editingConsumer.id}`, {
            onSuccess: () => {
                setEditingConsumer(null);
                setSearchResults([]);
                setSearchQuery('');
                setSearchPerformed(false);
                reset();
            },
        });
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Head title="Consumer ID Update" />

            <Container size="xl">
                <Box mb="xl">
                    <Title order={2} size="h3" mb="xs">Consumer ID Update</Title>
                    <Text c="dimmed" size="sm">
                        Search for a consumer by their current identification number (B-Form/CNIC) to update it. 
                        This action will recursively update all historical JSON snapshots in active and archived challans.
                    </Text>
                </Box>

                <Grid gutter="lg">
                    {/* Search Section */}
                    <Grid.Col span={{ base: 12, md: 5 }}>
                        <Paper withBorder shadow="sm" radius="md" p="xl">
                            <form onSubmit={handleSearch}>
                                <TextInput
                                    label="Search Consumer"
                                    description="Enter exact B-Form, CNIC, or Consumer Number"
                                    placeholder="e.g. 37405-1234567-1"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.currentTarget.value)}
                                    leftSection={<IconSearch size={16} stroke={1.5} />}
                                    mb="md"
                                />
                                <Button 
                                    type="submit" 
                                    fullWidth 
                                    loading={isSearching}
                                    disabled={!searchQuery.trim()}
                                >
                                    Search
                                </Button>
                            </form>

                            {searchPerformed && searchResults.length === 0 && !isSearching && (
                                <Alert icon={<IconAlertCircle size={16} />} title="Not Found" color="blue" mt="md" variant="light">
                                    No consumer found matching "{searchQuery}"
                                </Alert>
                            )}
                        </Paper>

                        {/* Search Results List */}
                        {searchResults.length > 0 && (
                            <Box mt="xl">
                                <Text fw={600} mb="sm">Select a Consumer:</Text>
                                <Grid>
                                    {searchResults.map((consumer) => {
                                        const profile = consumer.profile_details?.[0];
                                        return (
                                            <Grid.Col span={12} key={consumer.id}>
                                                <Card 
                                                    withBorder 
                                                    shadow="sm" 
                                                    radius="md" 
                                                    p="md"
                                                    style={{ 
                                                        cursor: 'pointer',
                                                        borderColor: editingConsumer?.id === consumer.id ? 'var(--mantine-color-blue-filled)' : undefined,
                                                        backgroundColor: editingConsumer?.id === consumer.id ? 'var(--mantine-color-blue-light)' : undefined,
                                                    }}
                                                    onClick={() => startEditing(consumer)}
                                                >
                                                    <Group justify="space-between" align="flex-start">
                                                        <div>
                                                            <Text fw={600} size="md">{profile?.name || 'Unknown Name'}</Text>
                                                            <Text size="sm" c="dimmed">S/D/O {profile?.father_or_guardian_name}</Text>
                                                        </div>
                                                        <Badge color={consumer.consumer_type === 'student' ? 'blue' : 'green'}>
                                                            {consumer.consumer_type}
                                                        </Badge>
                                                    </Group>
                                                    
                                                    <Group mt="md" gap="xs">
                                                        <Badge variant="dot" color="gray" leftSection={<IconId size={12}/>}>
                                                            {consumer.identification_number}
                                                        </Badge>
                                                        {profile?.class && (
                                                            <Badge variant="dot" color="cyan">{profile.class}</Badge>
                                                        )}
                                                    </Group>
                                                </Card>
                                            </Grid.Col>
                                        );
                                    })}
                                </Grid>
                            </Box>
                        )}
                    </Grid.Col>

                    {/* Edit Section */}
                    <Grid.Col span={{ base: 12, md: 7 }}>
                        {editingConsumer ? (
                            <Paper withBorder shadow="sm" radius="md" p="xl" style={{ position: 'sticky', top: 80 }}>
                                <Group mb="xl">
                                    <ActionIcon variant="light" color="blue" size="xl" radius="md">
                                        <IconId size={24} />
                                    </ActionIcon>
                                    <div>
                                        <Title order={3}>Update Identification Number</Title>
                                        <Text c="dimmed" size="sm">Changing ID for {editingConsumer.profile_details?.[0]?.name}</Text>
                                    </div>
                                </Group>

                                <Card withBorder bg="var(--mantine-color-gray-0)" mb="xl">
                                    <Grid>
                                        <Grid.Col span={6}>
                                            <Text size="xs" c="dimmed" fw={600} tt="uppercase">Consumer Number</Text>
                                            <Text fw={500}>{editingConsumer.consumer_number}</Text>
                                        </Grid.Col>
                                        <Grid.Col span={6}>
                                            <Text size="xs" c="dimmed" fw={600} tt="uppercase">Current ID</Text>
                                            <Text fw={500} c="red">{editingConsumer.identification_number}</Text>
                                        </Grid.Col>
                                        <Grid.Col span={12}>
                                            <Text size="xs" c="dimmed" fw={600} tt="uppercase">Institution</Text>
                                            <Text fw={500}>{editingConsumer.institution?.name || 'N/A'}</Text>
                                        </Grid.Col>
                                    </Grid>
                                </Card>

                                <form onSubmit={handleUpdate}>
                                    <TextInput
                                        withAsterisk
                                        label="New Identification Number (B-Form/CNIC)"
                                        description="This must be unique and not used by any other consumer."
                                        size="md"
                                        value={data.identification_number}
                                        onChange={(e) => setData('identification_number', e.currentTarget.value)}
                                        error={errors.identification_number}
                                        mb="xl"
                                    />
                                    
                                    <Alert icon={<IconAlertCircle size={16} />} title="Warning" color="orange" mb="xl">
                                        Updating this value will also rewrite the JSON snapshots of all active and history challans for this consumer. This action cannot be easily undone.
                                        <Box mt="sm">
                                            <Text size="sm" fw={600}>Records that will be modified:</Text>
                                            <ul style={{ margin: 0, paddingLeft: 20 }}>
                                                <li><Text size="sm"><b>1</b> row in <code>consumers</code> table</Text></li>
                                                <li><Text size="sm"><b>{editingConsumer.active_challans_count || 0}</b> rows in <code>active_challans</code> table</Text></li>
                                                <li><Text size="sm"><b>{editingConsumer.challan_histories_count || 0}</b> rows in <code>challan_history</code> table</Text></li>
                                            </ul>
                                        </Box>
                                    </Alert>

                                    <Group justify="flex-end">
                                        <Button variant="default" onClick={() => setEditingConsumer(null)}>
                                            Cancel
                                        </Button>
                                        <Button type="submit" loading={processing} color="blue" leftSection={<IconCheck size={16} />}>
                                            Confirm Update
                                        </Button>
                                    </Group>
                                </form>
                            </Paper>
                        ) : (
                            <Paper withBorder shadow="sm" radius="md" p="xl" style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', minHeight: 300, backgroundColor: 'var(--mantine-color-gray-0)' }}>
                                <IconSearch size={48} stroke={1} color="var(--mantine-color-gray-4)" />
                                <Text c="dimmed" mt="md">Search and select a consumer to edit</Text>
                            </Paper>
                        )}
                    </Grid.Col>
                </Grid>
            </Container>
        </AdminLayout>
    );
}
