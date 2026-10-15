import '@opensalt/ob3-definer/dist/ob3-definer';
import '@opensalt/ob3-definer/dist/ob3-definer.css';

// The credential pages render the ob3-definer widget mount point plus a
// definition form; wake the widget and capture its saveDefinition event into
// that form's content field. (Replaces the inline scripts previously in
// credential/new.html.twig and credential/edit.html.twig.)
document.addEventListener('DOMContentLoaded', () => {
    const contentField =
        document.getElementById('credential_definition_create_content')
        || document.getElementById('credential_definition_content_content');
    if (!contentField) {
        return;
    }

    window.dispatchEvent(new CustomEvent('ob3-open', { detail: { selector: '#ob3-definer' } }));
    window.addEventListener('saveDefinition', (e) => {
        contentField.value = e.detail;
        document.getElementById('credential_definition_form').submit();
    });
});
