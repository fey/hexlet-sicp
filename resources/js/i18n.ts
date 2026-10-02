import i18next, { type i18n } from 'i18next'
import { initReactI18next } from 'react-i18next'

// Строки приходят из PHP-словарей через shared prop `translations` (ADR 0003).
export function createI18n(lng: string, translations: Record<string, unknown>): i18n {
  const instance = i18next.createInstance()
  instance.use(initReactI18next).init({
    lng,
    resources: { [lng]: { translation: translations } },
    initAsync: false,
    interpolation: { escapeValue: false },
  })

  return instance
}
