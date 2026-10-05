const bootstrapApi = window.bootstrap;
if (bootstrapApi?.Tooltip) {
    bootstrapApi.Tooltip.getOrCreateInstance('body', {
        container: 'body',
        selector: '[data-bs-toggle="tooltip"]',
    });
}
