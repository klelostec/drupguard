
(function() {
    const toggleSetting = (setting, selected) => {
        selected = typeof selected === 'undefined' ?
            setting.classList.contains('visually-hidden') :
            selected
        ;
        if (selected) {
            setting.classList.remove('visually-hidden');
        }
        else {
            setting.classList.add('visually-hidden');
        }
    };

    function handler (container) {
        const type = container.parentNode.id.replace(/^Project_(.*)Plugins$/g, '$1'),
            select = container.querySelector('select'),
            settings = container.querySelectorAll('.' + type + '-settings');
        select.addEventListener('change', function(e) {
            settings.forEach((setting) => {
                const isSelected = setting.classList.contains(select.value + '-' + type + '-settings');
                toggleSetting(setting, isSelected);
            });
        });
        select.dispatchEvent(new Event('change'));
    }

    for (let type of ['source', 'build', 'analyse']) {
        const elements = document.querySelectorAll('div#Project_' + type + 'Plugins .field-collection-item');
        elements.forEach(handler);
    }

    document.addEventListener('ea.collection.item-added', (e) => {
        handler(e.detail.newElement);
    });

})();