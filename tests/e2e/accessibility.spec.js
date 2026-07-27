import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const login = async (page, email) => {
    await page.goto('/login');
    await page.getByLabel('Email Address').fill(email);
    await page.getByLabel('Password').fill('password');
    await page.getByRole('button', { name: 'Log In' }).click();
    await page.waitForLoadState('networkidle');
};

const expectNoSeriousAccessibilityViolations = async (page) => {
    await page.waitForLoadState('networkidle');
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        .analyze();
    const violations = results.violations
        .filter((violation) => ['serious', 'critical'].includes(violation.impact))
        .map((violation) => ({
            id: violation.id,
            impact: violation.impact,
            help: violation.help,
            nodes: violation.nodes.map((node) => ({
                target: node.target.join(' '),
                html: node.html,
                failureSummary: node.failureSummary,
            })),
        }));

    expect(
        violations,
        `Serious accessibility violations:\n${JSON.stringify(violations, null, 2)}`,
    ).toEqual([]);
};

test('public and login pages have no serious automated WCAG violations', async ({ page }) => {
    await page.goto('/');
    await expectNoSeriousAccessibilityViolations(page);

    await page.goto('/login');
    await expectNoSeriousAccessibilityViolations(page);
});

test('manager operating loop and action workspace have no serious automated WCAG violations', async ({ page }) => {
    await login(page, 'manager@acme.com');

    await page.goto('/home');
    await expectNoSeriousAccessibilityViolations(page);

    await page.goto('/actions');
    await expectNoSeriousAccessibilityViolations(page);
});

test('employee dashboard and respondent promise have no serious automated WCAG violations', async ({ page }) => {
    await login(page, 'employee5@acme.com');

    await page.goto('/employee');
    await expectNoSeriousAccessibilityViolations(page);

    const launchUrl = await page.getByRole('link', { name: 'Open Survey' }).getAttribute('href');
    await page.goto(launchUrl);
    await expect(page.locator('body')).toContainText('Empulse respondent data promise');
    await expectNoSeriousAccessibilityViolations(page);
});
