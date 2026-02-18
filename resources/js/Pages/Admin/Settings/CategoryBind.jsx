
import { useState, useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
    Title,
    Text,
    Stack,
    Box,
    Group,
    Card,
    Avatar,
    Badge,
    ActionIcon,
    SimpleGrid,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { IconSearch, IconEdit, IconTrash, IconPower } from '@tabler/icons-react';
import { AdminLayout } from '../../../Components/Layout';
import { adminNavItems } from '../../../config/navigation';
import {
    ThemedInput,
    ThemedButton,
    ThemedModal,
    ThemedMultiSelect,
} from '../../../Components/UI';
import axios from 'axios';

export default function CategoryBind() {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const [categories, setCategories] = useState([]);

    // Edit Modal State
    const [opened, { open, close }] = useDisclosure(false);
    const [editingStudent, setEditingStudent] = useState(null);

    // Form
    const { data, setData, put, processing, errors, reset } = useForm({
        name: '',
        father_name: '',
        category_ids: [],
    });

    // Fetch categories on mount
    useEffect(() => {
        axios.get('/admin/settings/categories')
            .then(res => {
                setCategories(res.data.data.map(c => ({
                    value: String(c.id),
                    label: c.label
                })));
            })
            .catch(err => console.error("Failed to fetch categories", err));
    }, []);

    const handleSearch = (e) => {
        e.preventDefault();
        setLoading(true);
        axios.post('/admin/settings/search', { query: searchQuery })
            .then(res => {
                setSearchResults(res.data.data);
            })
            .catch(err => console.error("Search failed", err))
            .finally(() => setLoading(false));
    };

    const handleEdit = (student) => {
        const profile = student.profile_details[0] || {};
        setEditingStudent(student);
        setData({
            name: profile.name || '',
            father_name: profile.father_or_guardian_name || '',
            category_ids: profile.fee_fund_category_ids ? profile.fee_fund_category_ids.map(String) : [],
        });
        open();
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        put(`/admin/settings/student/${editingStudent.id}`, {
            onSuccess: () => {
                close();
                // Refresh search results to show updated data
                handleSearch({ preventDefault: () => {} });
            },
        });
    };

    const handleToggleStatus = (id) => {
        if (confirm('Are you sure you want to toggle status?')) {
            router.put(`/admin/settings/student/${id}/status`, {}, {
                onSuccess: () => handleSearch({ preventDefault: () => {} }),
            });
        }
    };

    const handleSoftDelete = (id) => {
        if (confirm('Are you sure you want to delete this student?')) {
            router.delete(`/admin/settings/student/${id}`, {
                onSuccess: () => {
                    handleSearch({ preventDefault: () => {} });
                },
            });
        }
    };

    return (
        <AdminLayout navItems={adminNavItems}>
            <Stack gap="lg">
                <Box>
                    <Title order={2} mb={4}>Category Update</Title>
                    <Text c="dimmed" size="sm">
                        Search and manage student categories and details.
                    </Text>
                </Box>

                {/* Search Box */}
                <Card shadow="sm" padding="lg" radius="md" withBorder>
                    <form onSubmit={handleSearch}>
                        <Group>
                            <ThemedInput
                                placeholder="Search by ID, Name, or Consumer No."
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
                    {searchResults.map((student) => {
                        const profile = student.profile_details[0] || {};
                        return (
                            <Card key={student.id} shadow="sm" padding="lg" radius="md" withBorder>
                                <Group justify="space-between" mb="xs">
                                    <Badge color={student.is_active ? 'green' : 'red'} variant="light">
                                        {student.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                    <Group gap="xs">
                                        <ActionIcon variant="subtle" color="blue" onClick={() => handleEdit(student)}>
                                            <IconEdit size={16} />
                                        </ActionIcon>
                                        <ActionIcon variant="subtle" color={student.is_active ? 'orange' : 'green'} onClick={() => handleToggleStatus(student.id)}>
                                            <IconPower size={16} />
                                        </ActionIcon>
                                        <ActionIcon variant="subtle" color="red" onClick={() => handleSoftDelete(student.id)}>
                                            <IconTrash size={16} />
                                        </ActionIcon>
                                    </Group>
                                </Group>

                                <Group mb="md">
                                    <Avatar size="lg" radius="xl" color="blue">
                                        {profile.name ? profile.name.charAt(0) : 'S'}
                                    </Avatar>
                                    <Box>
                                        <Text fw={500}>{profile.name || 'Unknown Name'}</Text>
                                        <Text size="xs" c="dimmed">{student.identification_number}</Text>
                                    </Box>
                                </Group>

                                <Stack gap="xs">
                                    <Group justify="space-between">
                                        <Text size="sm" c="dimmed">Father Name:</Text>
                                        <Text size="sm">{profile.father_or_guardian_name || '-'}</Text>
                                    </Group>
                                    <Group justify="space-between">
                                        <Text size="sm" c="dimmed">Class / Section:</Text>
                                        <Text size="sm">{profile.class} / {profile.section}</Text>
                                    </Group>
                                    <Group justify="space-between">
                                        <Text size="sm" c="dimmed">Institution:</Text>
                                        <Text size="sm" truncate>{profile.institution_name}</Text>
                                    </Group>
                                     <Box>
                                        <Text size="sm" c="dimmed" mb={4}>Categories:</Text>
                                        <Group gap={4}>
                                            {profile.fee_fund_category_ids && profile.fee_fund_category_ids.length > 0 ? (
                                                profile.fee_fund_category_ids.map(id => {
                                                    const cat = categories.find(c => c.value === String(id));
                                                    return cat ? <Badge key={id} size="xs" variant="outline">{cat.label}</Badge> : null;
                                                })
                                            ) : (
                                                <Text size="xs" c="dimmed" fs="italic">No categories assigned</Text>
                                            )}
                                        </Group>
                                    </Box>
                                </Stack>
                            </Card>
                        );
                    })}
                </SimpleGrid>

                 {/* Edit Modal */}
                 <ThemedModal
                    opened={opened}
                    onClose={() => { close(); reset(); }}
                    title="Edit Student Details"
                    centered
                    size="lg"
                >
                    <form onSubmit={handleUpdate}>
                        <Stack>
                            <ThemedInput
                                label="Name"
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                                error={errors.name}
                                required
                            />
                            <ThemedInput
                                label="Father Name"
                                value={data.father_name}
                                onChange={e => setData('father_name', e.target.value)}
                                error={errors.father_name}
                            />

                            <ThemedMultiSelect
                                label="Fee Categories"
                                placeholder="Select categories"
                                data={categories}
                                value={data.category_ids}
                                onChange={(value) => setData('category_ids', value)}
                                searchable
                                nothingFoundMessage="No categories found"
                                checkIconPosition="right"
                            />

                            <Group justify="flex-end" mt="md">
                                <ThemedButton themeVariant="subtle" onClick={close}>Cancel</ThemedButton>
                                <ThemedButton type="submit" loading={processing}>Update Details</ThemedButton>
                            </Group>
                        </Stack>
                    </form>
                </ThemedModal>
            </Stack>
        </AdminLayout>
    );
}
