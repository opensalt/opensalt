import { test as base } from '@playwright/test';

type Role = 'Editor' | 'Admin' | 'User' | 'Super Editor' | 'Super User';

interface TestFixtures {
  loginPage: LoginPage;
  lastFrameworkId: string;
}

export const test = base.extend<TestFixtures>({
  loginPage: async ({ page }, use) => {
    await use(new LoginPage(page));
  },
  lastFrameworkId: async ({ page, request }, use) => {
    const documents = await request.get('http://web.salt-default/ims/case/v1p0/CFDocuments?sort=updatedAt&orderBy=DESC&limit=1000');
    const json = await documents.json();
    const docs = json.CFDocuments || [];

    let lastDoc = docs[0];
    for (const doc of docs) {
      if ((doc.adoptionStatus ?? 'Draft') !== 'Draft') continue;
      if (!lastDoc || lastDoc.updatedAt < doc.updatedAt) {
        lastDoc = doc;
      }
    }

    const identifier = lastDoc?.identifier;
    if (!identifier) {
      throw new Error('No framework found');
    }

    // Get document ID from URI redirect
    const uriPage = await page.goto(`http://web.salt-default/uri/${identifier}/${identifier}`);
    const url = page.url();
    const match = url.match(/\/cftree\/doc\/(\d+)/);
    const docId = match ? match[1] : '';

    await use(docId);
  },
});

export const expect = test.expect;

export { LoginPage };

class LoginPage {
  constructor(private page) {}

  async logout() {
    await this.page.goto('http://web.salt-default/logout');
  }

  async loginAsRole(role: Role) {
    await this.logout();
    await this.ensureUserExistsWithRole(role);
    await this.loginWithPassword(role);
  }

  async loginWithPassword(role: Role) {
    const credentials = this.getCredentialsForRole(role);

    await this.page.goto('http://web.salt-default/login');
    await this.page.fill('#username', credentials.username);
    await this.page.fill('#password', credentials.password);
    await this.page.click('button.btn-login');

    // Wait for successful login - check for main menu
    await this.page.waitForSelector('header a.dropdown-toggle svg[aria-label="Main Menu"]', { timeout: 60000 });

    // Verify logout link exists
    await this.page.click('header a.dropdown-toggle svg[aria-label="Main Menu"]');
    await this.page.waitForSelector('a.logout', { timeout: 60000 });
    await this.page.click('header a.dropdown-toggle svg[aria-label="Main Menu"]');

    // Go to editor
    await this.page.goto('http://web.salt-default/');
  }

  private getCredentialsForRole(role: Role): { username: string; password: string } {
    const credentialsMap = {
      'Editor': { username: 'editor_test@example.com', password: 'editor123#' },
      'Admin': { username: 'admin_test@example.com', password: 'admin123#' },
      'User': { username: 'user_test@example.com', password: 'user1234#' },
      'Super Editor': { username: 'seditor_test@example.com', password: 'super123#' },
      'Super User': { username: 'super_test@example.com', password: 'super123#' },
    };

    return credentialsMap[role] || { username: 'test@example.com', password: 'testing123#' };
  }

  private async ensureUserExistsWithRole(role: Role) {
    // In a real implementation, this would create a user via API
    // For now, we assume test users exist in the database
    console.log(`Ensuring user with role ${role} exists`);
  }
}
