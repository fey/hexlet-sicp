import { Link } from '@inertiajs/react'
import { Card, Grid, NavLink, Text } from '@mantine/core'
import { useTranslation } from 'react-i18next'
import AppLayout from './AppLayout'

type Props = {
  menu: App.DTO.Navigation.NavItemData[]
  children: React.ReactNode
}

export default function SettingsLayout({ menu, children }: Props) {
  const { t } = useTranslation()

  return (
    <AppLayout>
      <Grid my="md">
        <Grid.Col span={{ base: 12, md: 3 }}>
          <Card withBorder shadow="sm" p={0}>
            <Text fw={700} c="dimmed" px="md" py="sm">
              {t('account.settings')}
            </Text>
            {menu.map((item) =>
              item.inertia ? (
                <NavLink key={item.href} component={Link} href={item.href} label={item.label} active={item.active} variant="filled" />
              ) : (
                <NavLink key={item.href} href={item.href} label={item.label} active={item.active} variant="filled" />
              ),
            )}
          </Card>
        </Grid.Col>
        <Grid.Col span={{ base: 12, md: 9 }}>{children}</Grid.Col>
      </Grid>
    </AppLayout>
  )
}
