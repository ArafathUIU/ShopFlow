import { test, expect } from '@playwright/test';

const BASE_URL = process.env.BASE_URL || 'http://localhost:8000/api/v1';

async function apiRequest(context: any, method: string, path: string, data?: any) {
  const url = `${BASE_URL}${path}`;
  const options: any = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };

  if (data) {
    options.data = JSON.stringify(data);
  }

  const response = await context.request.fetch(url, options);
  return response;
}

test.describe('Critical user flows', () => {
  test('register → browse → cart → checkout', async ({ context }) => {
    const email = `test-${Date.now()}@example.com`;
    const password = 'password123';

    const registerRes = await apiRequest(context, 'POST', '/auth/register', {
      name: 'Test User',
      email,
      password,
      password_confirmation: password,
    });
    expect(registerRes.ok()).toBeTruthy();
    const registerData = await registerRes.json();
    expect(registerData.success).toBe(true);
    const token = registerData.data.token;

    const productsRes = await apiRequest(context, 'GET', '/products');
    expect(productsRes.ok()).toBeTruthy();
    const productsData = await productsRes.json();
    expect(productsData.success).toBe(true);
    expect(productsData.data.length).toBeGreaterThan(0);

    const productId = productsData.data[0].id;
    const addToCartRes = await apiRequest(context, 'POST', '/cart/items', {
      product_id: productId,
      quantity: 1,
    }, token);
    expect(addToCartRes.ok()).toBeTruthy();

    const cartRes = await apiRequest(context, 'GET', '/cart', undefined, token);
    expect(cartRes.ok()).toBeTruthy();
    const cartData = await cartRes.json();
    expect(cartData.success).toBe(true);
    expect(cartData.data.items.length).toBeGreaterThan(0);

    const orderRes = await apiRequest(context, 'POST', '/orders', {
      shipping_address: {
        name: 'Test User',
        address_line1: '123 Main St',
        city: 'New York',
        state: 'NY',
        postal_code: '10001',
        country: 'US',
      },
      billing_address: {
        name: 'Test User',
        address_line1: '123 Main St',
        city: 'New York',
        state: 'NY',
        postal_code: '10001',
        country: 'US',
      },
    }, token);
    expect(orderRes.ok()).toBeTruthy();
    const orderData = await orderRes.json();
    expect(orderData.success).toBe(true);
    expect(orderData.data.order_number).toBeDefined();
  });
});
