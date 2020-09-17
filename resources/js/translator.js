import FlashMessage from './components/FlashMessage';
import TranslatorFieldtype from './components/TranslatorFieldtype';

Statamic.booting(() => {
    Statamic.$components.register('flash-message', FlashMessage);
    Statamic.$components.register('translator-fieldtype', TranslatorFieldtype);
});
