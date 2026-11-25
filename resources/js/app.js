import './bootstrap';

function initErpUi() {
    if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
        window.HSStaticMethods.autoInit();
    }
}

document.addEventListener('DOMContentLoaded', initErpUi);
document.addEventListener('livewire:navigated', initErpUi);
document.addEventListener('livewire:initialized', () => {
    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        window.Livewire.hook('morph.updated', () => {
            initErpUi();
        });
    }
});
