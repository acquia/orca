// noinspection JSUnusedGlobalSymbols
module.exports = {
    '@tags': ['example'],
    before(browser) {
        browser.drupalInstall();
    },
    after(browser) {
        browser.drupalUninstall();
    },
    'Test page': browser => {
        browser
        .drupalRelativeURL('/')
        .waitForElementVisible('body', 1000)
        .assert.containsText('body', 'Log in')
        .drupalLogAndEnd({ onlyOnError: "FALSE" });
    },
};
