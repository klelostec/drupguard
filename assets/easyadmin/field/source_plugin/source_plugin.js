
(function() {
    const elements = document.querySelectorAll("div#Project_sourcePlugins .field-collection-item");
    const toggleSetting = (setting, selected) => {
        selected = typeof selected === 'undefined' ?
            setting.classList.contains("visually-hidden") :
            selected
        ;
        if (selected) {
            setting.classList.remove("visually-hidden");
        }
        else {
            setting.classList.add("visually-hidden");
        }
        setting.querySelectorAll('input, textarea, select').forEach((formElement) => {
            formElement.disabled=!selected;
        });
    };

    function handler (container) {
        const select = container.querySelector("select"),
            settings = container.querySelectorAll(".source-settings");
        settings.forEach((setting) => {
            toggleSetting(setting);
        });

        select.addEventListener('change', function(e) {
            settings.forEach((setting) => {
                const isSelected = setting.classList.contains(select.value + '-source-settings');
                toggleSetting(setting, isSelected);
            });
        });
        select.dispatchEvent(new Event("change"));
    }

    document.addEventListener('ea.collection.item-added', (e) => {
        handler(e.detail.newElement);
    });

    elements.forEach(handler);
})();