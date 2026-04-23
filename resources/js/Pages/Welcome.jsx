import { useState } from 'react';
import {
    Container,
    Title,
    Text,
    Button,
    Stack,
    Card,
    Badge,
    SimpleGrid,
    Group,
    Divider,
    Box,
    Center,
    TextInput,
    Transition,
    ActionIcon,
} from '@mantine/core';
import { useTheme } from '../theme';
import axios from 'axios';
import { Link } from '@inertiajs/react';

const SearchIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
    </svg>
);

const DownloadIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
);

export default function Welcome() {
    const { primaryColor, gradient, ui } = useTheme();
    const [consumerNumber, setConsumerNumber] = useState('');
    const [searching, setSearching] = useState(false);
    const [searchResult, setSearchResult] = useState(null);
    const [error, setError] = useState('');

    const handleSearch = async (e) => {
        if (e) e.preventDefault();

        if (!consumerNumber) {
            setError('Please enter a consumer number');
            return;
        }

        setSearching(true);
        setError('');
        setSearchResult(null);

        try {
            const response = await axios.get('/challan/search', {
                params: { consumer_number: consumerNumber }
            });
            if (response.data.success) {
                setSearchResult(response.data.data);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Challan not found. Please verify your consumer number.');
        } finally {
            setSearching(false);
        }
    };

    return (
        <Box
            style={{
                minHeight: '100vh',
                background: `linear-gradient(135deg, ${gradient.from} 0%, ${gradient.to} 100%)`,
                position: 'relative',
                overflow: 'hidden',
                fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
            }}
        >
            {/* Soft decorative circles */}
            <Box
                style={{
                    position: 'absolute',
                    top: '10%',
                    left: '5%',
                    width: '300px',
                    height: '300px',
                    borderRadius: '50%',
                    background: 'rgba(255, 255, 255, 0.1)',
                    filter: 'blur(80px)',
                }}
            />
            <Box
                style={{
                    position: 'absolute',
                    bottom: '10%',
                    right: '5%',
                    width: '400px',
                    height: '400px',
                    borderRadius: '50%',
                    background: 'rgba(0, 0, 0, 0.05)',
                    filter: 'blur(100px)',
                }}
            />

            <Container size="sm" py={120} style={{ position: 'relative', zIndex: 1 }}>
                <Stack gap={40} align="center">
                    {/* Branding Header */}
                    <Stack align="center" gap={5}>
                        <Box
                            style={{
                                background: 'rgba(255, 255, 255, 0.2)',
                                backdropFilter: 'blur(10px)',
                                padding: '15px',
                                borderRadius: '24px',
                                border: '1px solid rgba(255, 255, 255, 0.3)',
                                marginBottom: '10px',
                                boxShadow: '0 8px 32px rgba(0, 0, 0, 0.1)',
                            }}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </Box>
                        <Title
                            order={1}
                            c="white"
                            style={{
                                fontSize: '2.5rem',
                                fontWeight: 800,
                                letterSpacing: '-0.02em',
                                textShadow: '0 2px 10px rgba(0,0,0,0.1)',
                            }}
                        >
                            e-Challan Portal
                        </Title>
                        <Text c="white" opacity={0.8} fw={500} size="lg">
                            FGEI (C/G) Educational Institutions
                        </Text>
                    </Stack>

                    {/* Main Search Card */}
                    <Card
                        shadow="2xl"
                        p={40}
                        radius="32px"
                        style={{
                            width: '100%',
                            background: 'rgba(255, 255, 255, 0.85)',
                            backdropFilter: 'blur(20px)',
                            border: '1px solid rgba(255, 255, 255, 0.5)',
                            boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.15)',
                        }}
                    >
                        <form onSubmit={handleSearch}>
                            <Stack gap="xl">
                                <Box>
                                    <Text size="sm" fw={700} c="dimmed" tt="uppercase" ls="0.05em" mb={10} ml={5}>
                                        Enter Consumer Number
                                    </Text>
                                    <TextInput
                                        placeholder="e.g. 66100123456"
                                        size="xl"
                                        radius="xl"
                                        value={consumerNumber}
                                        onChange={(e) => setConsumerNumber(e.target.value)}
                                        error={error}
                                        styles={{
                                            input: {
                                                fontSize: '1.25rem',
                                                fontWeight: 600,
                                                paddingLeft: '30px',
                                                height: '64px',
                                                border: '2px solid rgba(0,0,0,0.05)',
                                                transition: 'all 0.2s ease',
                                                '&:focus': {
                                                    borderColor: primaryColor,
                                                    boxShadow: `0 0 0 4px ${primaryColor}15`,
                                                }
                                            }
                                        }}
                                    />
                                </Box>

                                <Button
                                    type="submit"
                                    size="xl"
                                    radius="xl"
                                    fullWidth
                                    variant="gradient"
                                    gradient={{ from: gradient.from, to: gradient.to }}
                                    loading={searching}
                                    leftSection={<SearchIcon />}
                                    style={{
                                        height: '64px',
                                        fontSize: '1.1rem',
                                        fontWeight: 700,
                                        boxShadow: `0 10px 20px -5px ${gradient.from}44`,
                                    }}
                                >
                                    Search My Challan
                                </Button>
                            </Stack>
                        </form>

                        {/* Search Results */}
                        <Transition
                            mounted={!!searchResult}
                            transition="pop-top-left"
                            duration={400}
                            timingFunction="ease"
                        >
                            {(styles) => (
                                <Box style={{ ...styles, marginTop: '40px' }}>
                                    <Divider mb="xl" label="Result Found" labelPosition="center" />
                                    <Stack gap="xl">
                                        <Card
                                            p="xl"
                                            radius="24px"
                                            withBorder
                                            style={{
                                                background: searchResult?.is_paid ? 'rgba(232, 255, 243, 0.7)' : 'rgba(255, 255, 255, 0.5)',
                                                borderStyle: searchResult?.is_paid ? 'solid' : 'dashed',
                                                borderColor: searchResult?.is_paid ? '#27ae60' : 'rgba(0,0,0,0.1)',
                                            }}
                                        >
                                            <Stack gap="xl">
                                                <Center>
                                                    {searchResult?.is_paid ? (
                                                        <Badge size="xl" variant="filled" color="green" style={{ height: 40, padding: '0 25px' }}>✅ CHALLAN PAID</Badge>
                                                    ) : (
                                                        <Badge size="xl" variant="light" color="blue" style={{ height: 40, padding: '0 25px' }}>📄 CHALLAN FOUND</Badge>
                                                    )}
                                                </Center>

                                                <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="xl">
                                                    <Stack gap={5}>
                                                        <Text size="xs" c="dimmed" fw={700} tt="uppercase" ls="0.05em">Student Name</Text>
                                                        <Text fw={700} size="xl" c="dark">{searchResult?.name}</Text>
                                                    </Stack>
                                                    <Stack gap={5}>
                                                        <Text size="xs" c="dimmed" fw={700} tt="uppercase" ls="0.05em">Challan Number</Text>
                                                        <Text fw={700} size="xl" c="dark">{searchResult?.challan_no}</Text>
                                                    </Stack>
                                                    <Stack gap={5}>
                                                        <Text size="xs" c="dimmed" fw={700} tt="uppercase" ls="0.05em">Class / Section</Text>
                                                        <Text fw={700} size="lg" c="dark">{searchResult?.class} - {searchResult?.section}</Text>
                                                    </Stack>
                                                    <Stack gap={5}>
                                                        <Text size="xs" c="dimmed" fw={700} tt="uppercase" ls="0.05em">Payable Amount</Text>
                                                        <Text fw={800} size="2rem" c={primaryColor}>Rs. {searchResult?.amount}</Text>
                                                    </Stack>
                                                </SimpleGrid>

                                                <Button
                                                    component="a"
                                                    href={searchResult?.view_url}
                                                    target="_blank"
                                                    size="lg"
                                                    radius="xl"
                                                    fullWidth
                                                    mt={10}
                                                    variant="filled"
                                                    color={searchResult?.is_paid ? "green" : "dark"}
                                                    leftSection={searchResult?.is_paid ? <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="m9 15 2 2 4-4"/></svg> : <DownloadIcon />}
                                                    style={{ height: '56px' }}
                                                >
                                                    {searchResult?.is_paid ? "View Paid Receipt" : "Print Challan Form"}
                                                </Button>
                                            </Stack>
                                        </Card>
                                    </Stack>
                                </Box>
                            )}
                        </Transition>
                    </Card>

                    {/* Simple Footer */}
                    <Stack gap="xs" align="center">
                        <Text size="sm" c="white" opacity={0.7}>
                            © {new Date().getFullYear()} FGEI e-Portal. All rights reserved.
                        </Text>
                        <Group gap="xs">
                            <Text size="xs" c="white" opacity={0.5}>Are you an administrator?</Text>
                            <Link
                                href="/login"
                                style={{
                                    color: 'white',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    textDecoration: 'underline',
                                    opacity: 0.8,
                                }}
                            >
                                Login Here
                            </Link>
                        </Group>
                    </Stack>
                </Stack>
            </Container>

            <style>{`
                @keyframes pulse {
                    0% { transform: scale(1); opacity: 0.8; }
                    50% { transform: scale(1.05); opacity: 1; }
                    100% { transform: scale(1); opacity: 0.8; }
                }
            `}</style>
        </Box>
    );
}
