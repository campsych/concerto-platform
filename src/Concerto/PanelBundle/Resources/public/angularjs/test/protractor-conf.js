exports.config = {
    framework: 'jasmine',
    baseUrl: 'http://concerto.przemyslawlis.com/app_test.php',
    seleniumAddress: 'http://127.0.0.1:4444/wd/hub',
    specs: [
        'e2e/page_objects/*.js',
        'e2e/scenarios/*.js'
    ],
    capabilities: {
        browserName: 'firefox'
    },
    jasmineNodeOpts: {
        showColors: true
    },
    onPrepare: function () {
        browser.driver.get('http://concerto.przemyslawlis.com/app_test.php/login');

        browser.driver.findElement(by.id('username')).sendKeys('admin');
        browser.driver.findElement(by.id('password')).sendKeys('admin');
        browser.driver.findElement(by.id('btn-login')).click();

        return browser.driver.wait(function () {
            return browser.driver.getCurrentUrl().then(function (url) {
                return /admin/.test(url);
            });
        }, 10000);
    }
};
