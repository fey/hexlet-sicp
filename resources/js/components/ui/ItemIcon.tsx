import { IconCode, IconDownload, IconMessages, IconShieldLock, type IconUser, IconUsers } from '@tabler/icons-react'

// Имя иконки приходит с бэкенда в NavItemData::$icon.
const icons: Record<string, typeof IconUser> = {
  'shield-lock': IconShieldLock,
  users: IconUsers,
  messages: IconMessages,
  code: IconCode,
  download: IconDownload,
}

export function ItemIcon({ name }: { name: string | null }) {
  const Icon = name ? icons[name] : null
  return Icon ? <Icon size={16} /> : null
}
