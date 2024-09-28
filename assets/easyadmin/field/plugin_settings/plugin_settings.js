const pluginCollectionHandler = function (event) {
    document.querySelectorAll('.plugin-collection[data-ea-collection-field]').forEach((collection) => {
        const pluginType = collection.dataset.pluginCollectionType;
        collection.querySelectorAll(' .field-collection-item').forEach((collectionItem) => {
            if (!collectionItem || collectionItem.classList.contains('plugin-collection-item-processed')) {
                return;
            }

            PluginEaCollectionProperty.handlePluginItem(pluginType, collectionItem);
        });

        if (collection.classList.contains('plugin-collection-processed')) {
            PluginEaCollectionProperty.handlePlugin(collection);
        }
    });
}
const pluginMinMaxCollectionHandler = function(event) {
    document.querySelectorAll('.plugin-collection[data-ea-collection-field][data-plugin-collection-min], .plugin-collection[data-ea-collection-field][data-plugin-collection-max]').forEach((collection) => {
        const length = collection.querySelectorAll(' .field-collection-item').length,
            min = parseInt(collection.dataset.pluginCollectionMin ?? 0),
            max = parseInt(collection.dataset.pluginCollectionMax ?? Number.MAX_SAFE_INTEGER),
            addButton = collection.querySelector('button.field-collection-add-button'),
            deleteButtons = collection.querySelectorAll('button.field-collection-delete-button');

        if(max>length) {
            addButton.classList.remove('visually-hidden');
        }
        else {
            addButton.classList.add('visually-hidden');
        }

        if (min<length) {
            deleteButtons.forEach((deleteButton) => {
                deleteButton.classList.remove('visually-hidden');
            });
        }
        else {
            deleteButtons.forEach((deleteButton) => {
                deleteButton.classList.add('visually-hidden');
            });
        }
    });
}

window.addEventListener('DOMContentLoaded', pluginCollectionHandler);
window.addEventListener('DOMContentLoaded', pluginMinMaxCollectionHandler);
document.addEventListener('ea.collection.item-added', pluginCollectionHandler);
document.addEventListener('ea.collection.item-added', pluginMinMaxCollectionHandler);
document.addEventListener('ea.collection.item-removed', pluginMinMaxCollectionHandler);

const PluginEaCollectionProperty = {
    selectValue: (pluginType, selectedValue, collectionItem) => {
        collectionItem.querySelectorAll('div.' + pluginType + '-settings').forEach(function (subform) {
            const selected = subform.classList.contains(selectedValue + '-' + pluginType + '-settings');
            if (selected) {
                subform.classList.remove('visually-hidden');
            }
            else {
                subform.classList.add('visually-hidden');
            }
        })
    },
    handlePluginItem: (pluginType, collectionItem) => {
        const select = collectionItem.querySelector('div.' + pluginType + '-plugin-type select');
        select.addEventListener('change', (e) => {
            const selectedValue = e.currentTarget.value;
            PluginEaCollectionProperty.selectValue(pluginType, selectedValue, collectionItem);
        });
        PluginEaCollectionProperty.selectValue(pluginType, select.value, collectionItem);
        collectionItem.classList.add('plugin-collection-item-processed');
    },
};