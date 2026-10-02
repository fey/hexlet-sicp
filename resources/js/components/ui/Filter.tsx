import { Link, useForm } from '@inertiajs/react'
import { ActionIcon, Group, TextInput } from '@mantine/core'
import { IconSearch, IconX } from '@tabler/icons-react'
import { useTranslation } from 'react-i18next'

type Field = {
  name: string
  placeholder: string
}

type Props = {
  action: string
  fields: Field[]
  values: Record<string, string | null>
}

// Поля уходят как filter[<name>] — контракт spatie/laravel-query-builder на бэкенде.
export function Filter({ action, fields, values }: Props) {
  const { t } = useTranslation()
  const form = useForm({
    filter: Object.fromEntries(fields.map((field) => [field.name, values[field.name] ?? ''])),
  })

  const submit = (event: React.FormEvent) => {
    event.preventDefault()
    form.get(action, { preserveState: true, replace: true })
  }

  return (
    <form onSubmit={submit}>
      <Group mb="md" wrap="nowrap" align="flex-end">
        {fields.map((field) => (
          <TextInput
            key={field.name}
            flex={1}
            name={`filter[${field.name}]`}
            aria-label={field.placeholder}
            placeholder={field.placeholder}
            value={form.data.filter[field.name]}
            onChange={(event) => form.setData('filter', { ...form.data.filter, [field.name]: event.target.value })}
          />
        ))}
        <ActionIcon type="submit" size="lg" aria-label={t('layout.common.search')} loading={form.processing}>
          <IconSearch size={18} />
        </ActionIcon>
        <ActionIcon component={Link} href={action} variant="default" size="lg" aria-label={t('layout.common.reset')}>
          <IconX size={18} />
        </ActionIcon>
      </Group>
    </form>
  )
}
