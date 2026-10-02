import { createTheme } from '@mantine/core'

// Шрифт — системный стек: Onest из _variables.scss объявлен, но никогда не подключался.
export const theme = createTheme({
  primaryColor: 'blue',
  defaultRadius: 'sm',
  fontFamily:
    'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
})
