import messages from '@intlify/unplugin-vue-i18n/messages';
import { createI18n } from 'vue-i18n';
import { DEFAULT_LANGUAGE } from '@Constants';

const i18n = createI18n({
  fallbackLocale: 'en',
  fallbackWarn: false,
  legacy: false,
  locale: DEFAULT_LANGUAGE || navigator.language || 'en',
  messages,
  missingWarn: false,
});

export default i18n;
