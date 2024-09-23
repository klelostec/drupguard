import "./machine_name.scss";

import { slugify } from 'transliteration';
(function() {
    const trimByChar = (string, character) => {
        const first = [...string].findIndex((char) => char !== character);
        const last = [...string].reverse().findIndex((char) => char !== character);
        return string.substring(first, string.length - last);
    };

    const prepareMachineName = (source) => {
        const rx = new RegExp("[^a-z0-9_]+", 'g');
        return trimByChar(
            source
                .toLowerCase()
                .replace(rx, "_"),
            "_",
        );
    }
    const transliterate = (source) => {
        slugify.config({
            lowercase: true,
            trim: true,
            separator: "_",
            allowedChars: "[^a-z0-9_]+"
        });
        return prepareMachineName(slugify(source));
    }

    const showMachineName = (machine_name, elementSpan, elementInput, parent) => {
        if (!machine_name) {
            parent.classList.add("visually-hidden");
        }
        else {
            parent.classList.remove("visually-hidden");
        }
        elementSpan.textContent = machine_name;
        elementInput.value = machine_name;
    };

    const machineNameHandler = (baseValue, elementSpan, elementInput, parent) => {
        const needsTransliteration = !/^[A-Za-z0-9_\s]*$/.test(baseValue);
        if (needsTransliteration) {
            showMachineName(transliterate(baseValue), elementSpan, elementInput, parent);
        } else {
            showMachineName(prepareMachineName(baseValue), elementSpan, elementInput, parent);
        }
    };

    const elements = document.querySelectorAll(".field-machine-name input[data-source-field]");
    elements.forEach((input) => {
        const autofill = input.parentNode.querySelector(".field-machine-name-autofill"),
            autofillEdit = autofill.querySelector("a"),
            autofillSpan = autofill.querySelector("span"),
            source = document.querySelector("input[name=\"" + input.dataset.sourceField + "\"]"),
            parent = input.parentElement.parentElement,
            listener = (e) => {
                machineNameHandler(e.target.value, autofillSpan, input, parent);
            };

        input.classList.add("visually-hidden");
        autofill.classList.remove("visually-hidden");
        source.addEventListener('keyup', listener);
        source.dispatchEvent(new Event("keyup"));
        autofillEdit.addEventListener('click', function(e) {
            e.preventDefault();
            source.removeEventListener('keyup', listener);
            input.classList.remove("visually-hidden");
            autofill.classList.add("visually-hidden");
        });
        input.addEventListener('keypress', function(e) {
            if (e.keyCode < 31 || e.keyCode===127 || e.keyCode===95) {
                return;
            }
            if (e.key !== transliterate(e.key)) {
                e.preventDefault();
            }
        });
    });
})();