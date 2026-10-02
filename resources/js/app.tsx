import '@mantine/core/styles.css'
import '@mantine/notifications/styles.css'
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react'
import { MantineProvider } from '@mantine/core'
import { ModalsProvider } from '@mantine/modals'
import { Notifications } from '@mantine/notifications'
import { createRoot } from 'react-dom/client'
import { I18nextProvider } from 'react-i18next'
import { createI18n } from './i18n'
import { theme } from './theme'

createInertiaApp({
  title: (title) => (title ? `${title} - Hexlet SICP` : 'Hexlet SICP'),
  resolve: (name) => {
    const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.tsx')
    return pages[`./pages/${name}.tsx`]()
  },
  setup({ el, App, props }) {
    const { locale, translations, colorScheme } = props.initialPage.props

    createRoot(el).render(
      <I18nextProvider i18n={createI18n(locale, translations)}>
        <MantineProvider theme={theme} forceColorScheme={colorScheme}>
          <ModalsProvider>
            <Notifications />
            <App {...props} />
          </ModalsProvider>
        </MantineProvider>
      </I18nextProvider>,
    )
  },
  progress: {
    color: '#4B5563',
  },
})
