import { useState } from 'react';
import { useForm, Head } from '@inertiajs/react';
import {
    Container,
    Paper,
    Title,
    Text,
    TextInput,
    PasswordInput,
    Button,
    Stack,
    Box,
    Alert,
    Center,
} from '@mantine/core';
import { useTheme } from '../../theme';

export default function Login() {
    const { gradient, ui } = useTheme();
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Login" />
            <Box
                style={{
                    minHeight: '100vh',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: `linear-gradient(135deg, ${gradient.from} 0%, ${gradient.to} 100%)`,
                    padding: '20px',
                }}
            >
                <Container size={420}>
                    <Paper
                        radius="lg"
                        p="xl"
                        style={{
                            background: ui.cardBg,
                            backdropFilter: 'blur(10px)',
                            border: '1px solid rgba(255,255,255,0.2)',
                            boxShadow: '0 8px 32px rgba(0,0,0,0.2)',
                        }}
                    >
                        <Stack align="center" mb="lg">
                            <Box
                                style={{
                                    width: 60,
                                    height: 60,
                                    borderRadius: '50%',
                                    background: `linear-gradient(135deg, ${gradient.from}, ${gradient.to})`,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    boxShadow: '0 4px 15px rgba(0,0,0,0.2)',
                                }}
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </Box>
                            <Title order={2} style={{ color: '#1a1b1e' }}>Admin Portal</Title>
                            <Text c="dimmed" size="sm">Please sign in to continue</Text>
                        </Stack>

                        <form onSubmit={submit}>
                            <Stack>
                                {errors.username && !errors.password && (
                                    <Alert color="red" radius="md">
                                        {errors.username}
                                    </Alert>
                                )}

                                <TextInput
                                    label="Username"
                                    placeholder="Enter your username"
                                    required
                                    value={data.username}
                                    onChange={(e) => setData('username', e.target.value)}
                                    error={errors.username}
                                    radius="md"
                                />

                                <PasswordInput
                                    label="Password"
                                    placeholder="Enter your password"
                                    required
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    error={errors.password}
                                    radius="md"
                                />

                                <Button
                                    fullWidth
                                    mt="md"
                                    size="md"
                                    radius="md"
                                    type="submit"
                                    loading={processing}
                                    variant="gradient"
                                    gradient={{ from: gradient.from, to: gradient.to }}
                                >
                                    Sign In
                                </Button>
                            </Stack>
                        </form>
                    </Paper>
                </Container>
            </Box>
        </>
    );
}
