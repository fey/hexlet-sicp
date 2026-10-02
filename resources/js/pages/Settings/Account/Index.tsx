import { Head, router } from '@inertiajs/react'
import { Anchor, Button, Card, Stack, Text, Title } from '@mantine/core'
import { modals } from '@mantine/modals'
import { useTranslation } from 'react-i18next'
import SettingsLayout from '@/layouts/SettingsLayout'

export default function AccountIndex({ email, resetPasswordUrl, destroyUrl, menu }: App.DTO.Settings.AccountPageData) {
  const { t } = useTranslation()

  // Деструктивное действие: подтверждение в модалке + явный router.delete(), а не data-method/data-confirm.
  const confirmDelete = () =>
    modals.openConfirmModal({
      title: t('account.delete_account'),
      children: <Text size="sm">{t('account.are_you_sure')}</Text>,
      labels: { confirm: t('account.delete_account'), cancel: t('layout.common.cancel') },
      confirmProps: { color: 'red' },
      onConfirm: () => router.delete(destroyUrl),
    })

  return (
    <SettingsLayout menu={menu}>
      <Head title={t('account.account')} />
      <Stack>
        <Card withBorder>
          <Title order={3} mb="sm">
            {t('account.account')}
          </Title>
          <Text>
            {t('account.current_email')}: {email}
          </Text>
        </Card>
        <Card withBorder>
          <Title order={3} mb="sm">
            {t('settings.account.password')}
          </Title>
          {/* Страница восстановления пароля ещё на Blade — обычная ссылка, не <Link>. */}
          <Anchor href={resetPasswordUrl}>{t('settings.account.reset_password')}</Anchor>
        </Card>
        <Card withBorder>
          <Button color="red" w="fit-content" onClick={confirmDelete}>
            {t('account.delete_account')}
          </Button>
        </Card>
      </Stack>
    </SettingsLayout>
  )
}
