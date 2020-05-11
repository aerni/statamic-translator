<template>
    <div style="background: #f1f5f9;">
        <div v-if="isEditingDefaultLocale">
            <flash-message 
                :title="translate_choice('addons.Translator::fieldtype.error_unavailable')" 
                :text="translate_choice('addons.Translator::fieldtype.error_default_locale')"
                type="info" 
            ></flash-message>
        </div>
        <div v-else>
            <div v-if="!isSupportedSourceLanguage || !isSupportedTargetLanguage">
                <div v-if="!isSupportedSourceLanguage">
                    <flash-message 
                        :title="translate_choice('addons.Translator::fieldtype.error_unavailable')" 
                        :text="translate_choice('addons.Translator::fieldtype.error_source_locale')"
                        type="info" 
                    ></flash-message>
                </div>
                <div v-else>
                    <flash-message 
                        :title="translate_choice('addons.Translator::fieldtype.error_unavailable')" 
                        :text="translate_choice('addons.Translator::fieldtype.error_target_locale')"
                        type="info" 
                    ></flash-message>
                </div>
            </div>
            <div v-else>
                <div v-if="idle">
                    <div class="rounded-md p-2 border">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733 18.992 18.992 0 01-1.487-2.494 1 1 0 111.79-.89c.234.47.489.928.764 1.372.417-.934.752-1.913.997-2.927H3a1 1 0 110-2h3V3a1 1 0 011-1zm6 6a1 1 0 01.894.553l2.991 5.982a.869.869 0 01.02.037l.99 1.98a1 1 0 11-1.79.895L15.383 16h-4.764l-.724 1.447a1 1 0 11-1.788-.894l.99-1.98.019-.038 2.99-5.982A1 1 0 0113 8zm-1.382 6h2.764L13 11.236 11.618 14z" clip-rule="evenodd" fill-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-2">
                                <button @click="translate" type="button" class="btn btn-default">
                                    {{ buttonText }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="translating">
                    <flash-message 
                        :title="translate_choice('addons.Translator::fieldtype.translating_title')" 
                        :text="translate_choice('addons.Translator::fieldtype.translating_message')"
                        type="loading" 
                    ></flash-message>
                </div>
                <div v-if="translated">
                    <flash-message 
                        :title="translate_choice('addons.Translator::fieldtype.success')" 
                        :text="translate_choice('addons.Translator::fieldtype.reload')"
                        type="success" 
                    ></flash-message>
                </div>
                <div v-if="error">
                    <flash-message 
                        :title="translate_choice('addons.Translator::fieldtype.error_general')" 
                        :text="translate_choice('addons.Translator::fieldtype.error_console')"
                        type="error" 
                    ></flash-message>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    mixins: [Fieldtype],

    data() {
        return {
            idle: true,
            translating: false,
            translated: false,
            error: null,
        };
    },

    computed: {

        defaultLocale() {
            return Object.keys(Statamic.locales)[0];
        },
        
        currentLocale() {
            return Statamic.Publish ? (Statamic.Publish.locale || defaultLocale) : defaultLocale;
        },
        
        isEditingDefaultLocale() {
            return this.currentLocale === this.defaultLocale;
        },
        
        isSupportedSourceLanguage() {
            return this.data.supportedLanguages.find(language => {
                if (language === this.defaultLocale) return true;
            });
        },
        
        isSupportedTargetLanguage() {
            return this.data.supportedLanguages.find(language => {
                if (language === this.currentLocale) return true;
            });
        },

        id() {
            return this.$parent.$parent.$parent.$parent.uuid;
        },

        buttonText() {
            return this.data.buttonText ? this.data.buttonText : translate_choice('addons.Translator::fieldtype.translate');
        },

    },

    methods: {
        translate() {
            this.idle = false;
            this.translating = true;
            this.translated = false;

            axios.post('/!/translator/translate', {
                id: this.id,
                targetLocale: this.currentLocale,
            })
            .then(response => {
                this.idle = false,
                this.translating = false;
                this.translated = true;
                this.reloadPage(3000);
            })
            .catch(error => {
                this.idle = false;
                this.translating = false;
                this.translated = false;
                this.error = error.response.data;
                console.log(error.response);
            });
        },

        reloadPage(timeout) {
            setTimeout(() => {
                location.reload();
            }, timeout);
        },
    }
};
</script>