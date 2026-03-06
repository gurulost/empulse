import { expect, test } from '@playwright/test';

const expectHealthyPage = async (page, path, marker = null) => {
    const pageErrors = [];
    page.on('pageerror', (error) => pageErrors.push(error.message));

    await page.goto(path);
    await page.waitForLoadState('networkidle');

    if (marker) {
        await expect(page.locator('body')).toContainText(marker);
    }

    const text = (await page.locator('body').innerText()).trim();
    expect(text.length).toBeGreaterThan(30);
    expect(pageErrors).toEqual([]);
};

const login = async (page, email, password = 'password') => {
    await page.goto('/login');
    await page.getByLabel('Email Address').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Log In' }).click();
    await page.waitForLoadState('networkidle');
};

test('guest routes render without blank screens', async ({ page }) => {
    await expectHealthyPage(page, '/', 'Empulse');
    await expectHealthyPage(page, '/login', 'Log In');
    await expectHealthyPage(page, '/register', 'Create Account');
});

test('workfit admin routes render', async ({ page }) => {
    await login(page, 'admin@workfit.com');
    await expectHealthyPage(page, '/home', 'Dashboard Analytics');
    await expectHealthyPage(page, '/reports', 'Reports');
    await expectHealthyPage(page, '/admin', 'Companies');
    await expectHealthyPage(page, '/admin/builder', 'Survey Status');
});

test('workfit admin user list shows chief role label', async ({ page }) => {
    await login(page, 'admin@workfit.com');
    await page.goto('/admin');
    await page.waitForLoadState('networkidle');
    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForLoadState('networkidle');
    await page.getByPlaceholder('Search users...').fill('chief@acme.com');
    await page.getByPlaceholder('Search users...').press('Enter');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('tbody')).toContainText('chief@acme.com');
    await expect(page.locator('tbody')).toContainText('Chief');
});

test('workfit admin onboarding tab renders', async ({ page }) => {
    await login(page, 'admin@workfit.com');
    await page.goto('/admin');
    await page.waitForLoadState('networkidle');
    await page.getByRole('link', { name: 'Onboarding' }).click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('body')).toContainText('Activation By Company');
    await expect(page.locator('body')).toContainText('Survey Content Status');
});

test('manager routes render', async ({ page }) => {
    await login(page, 'manager@acme.com');
    await expectHealthyPage(page, '/home', 'Dashboard Analytics');
    await expectHealthyPage(page, '/reports', 'Reports');
    await expectHealthyPage(page, '/team/manage');
    await expectHealthyPage(page, '/surveys/manage', 'Survey Overview');
    await expectHealthyPage(page, '/survey-waves', 'Existing Waves');
    await expectHealthyPage(page, '/account/billing', 'Account & Billing');
});

test('chief routes render', async ({ page }) => {
    await login(page, 'chief@acme.com');
    await expectHealthyPage(page, '/home', 'Dashboard Analytics');
    await expectHealthyPage(page, '/reports', 'Reports');
    await expectHealthyPage(page, '/team/manage');
});

test('team lead routes render', async ({ page }) => {
    await login(page, 'lead@acme.com');
    await expectHealthyPage(page, '/home', 'Dashboard Analytics');
    await expectHealthyPage(page, '/reports', 'Reports');
    await expectHealthyPage(page, '/team/manage');
});

test('employee dashboard renders', async ({ page }) => {
    await login(page, 'employee1@acme.com');
    await expectHealthyPage(page, '/employee', 'Employee Dashboard');
    await expect(page.locator('body')).toContainText('Before you start');
    await expect(page.locator('body')).toContainText('Progress autosaves');
});
