import { test as base } from '@playwright/test';

export const test = base.extend({
  apiToken: async ({}, use) => {
    const baseURL = process.env.BASE_URL || 'http://localhost:8000/api/v1';
    const email = `playwright-${Date.now()}@example.com`;
    const password = 'password123';

    const registerRes = await fetch(`${baseURL}/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: 'Playwright User',
        email,
        password,
        password_confirmation: password,
      }),
    });

    const registerData = await registerRes.json();
    const token = registerData.data.token;

    await use(token);
  },
});

export { expect } from '@playwright/test';
