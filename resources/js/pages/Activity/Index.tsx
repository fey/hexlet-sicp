import { Head } from '@inertiajs/react'
import { Anchor, List, Table, Text, Title } from '@mantine/core'
import { useTranslation } from 'react-i18next'
import { Pagination } from '@/components/ui/Pagination'
import AppLayout from '@/layouts/AppLayout'

type Item = App.DTO.Activity.ActivityItemData

// Ссылки ведут на Blade-страницы и на внешнюю книгу — обычный <a>, не <Link>.
function ActivityLink({ link }: { link: App.DTO.Activity.ActivityLinkData }) {
  return link.href ? <Anchor href={link.href}>{link.label}</Anchor> : <Text span>{link.label}</Text>
}

function Description({ item }: { item: Item }) {
  if (item.links.length > 1) {
    return (
      <>
        <Text>{item.description}</Text>
        <List size="sm">
          {item.links.map((link) => (
            <List.Item key={link.label}>
              <ActivityLink link={link} />
            </List.Item>
          ))}
        </List>
      </>
    )
  }

  return (
    <Text>
      {item.description} {item.links[0] && <ActivityLink link={item.links[0]} />}
    </Text>
  )
}

export default function ActivityIndex({ items, pagination }: App.DTO.Activity.ActivityPageData) {
  const { t } = useTranslation()

  return (
    <AppLayout>
      <Head title={t('activitylog.title')} />
      <Title order={1} size="h3" my="md">
        {t('activitylog.title')}
      </Title>
      <Table.ScrollContainer minWidth={600}>
        <Table striped>
          <Table.Thead>
            <Table.Tr>
              <Table.Th>{t('activitylog.user')}</Table.Th>
              <Table.Th>{t('activitylog.description')}</Table.Th>
              <Table.Th>{t('activitylog.time')}</Table.Th>
            </Table.Tr>
          </Table.Thead>
          <Table.Tbody>
            {items.map((item) => (
              <Table.Tr key={item.id}>
                <Table.Td>{item.causerUrl && <Anchor href={item.causerUrl}>{item.causerName}</Anchor>}</Table.Td>
                <Table.Td>
                  <Description item={item} />
                </Table.Td>
                <Table.Td>{item.createdAt}</Table.Td>
              </Table.Tr>
            ))}
          </Table.Tbody>
        </Table>
      </Table.ScrollContainer>
      <Pagination pagination={pagination} />
    </AppLayout>
  )
}
