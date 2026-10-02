import { Link, usePage } from '@inertiajs/react'
import { Anchor, type AnchorProps, Menu } from '@mantine/core'

type NavItem = App.DTO.Navigation.NavItemData

// <Link> только на Inertia-маршруты; Blade-страница отдаёт HTML, которого <Link> не ждёт.
// POST-пункты (выход, dev-login) — нативная форма: ответ — редирект на Blade-страницу.
function PostForm({ item, children }: { item: NavItem; children: React.ReactNode }) {
  const { csrfToken } = usePage().props

  return (
    <form method="post" action={item.href} style={{ display: 'contents' }}>
      <input type="hidden" name="_token" value={csrfToken} />
      {children}
    </form>
  )
}

export function NavAnchor({ item, ...props }: { item: NavItem } & AnchorProps) {
  if (item.method === 'post') {
    return (
      <PostForm item={item}>
        <Anchor component="button" type="submit" {...props}>
          {item.label}
        </Anchor>
      </PostForm>
    )
  }

  if (item.inertia) {
    return (
      <Anchor component={Link} href={item.href} {...props}>
        {item.label}
      </Anchor>
    )
  }

  return (
    <Anchor href={item.href} {...props}>
      {item.label}
    </Anchor>
  )
}

export function NavMenuItem({ item, leftSection }: { item: NavItem; leftSection?: React.ReactNode }) {
  if (item.method === 'post') {
    return (
      <PostForm item={item}>
        <Menu.Item type="submit" leftSection={leftSection}>
          {item.label}
        </Menu.Item>
      </PostForm>
    )
  }

  if (item.inertia) {
    return (
      <Menu.Item component={Link} href={item.href} leftSection={leftSection}>
        {item.label}
      </Menu.Item>
    )
  }

  return (
    <Menu.Item component="a" href={item.href} leftSection={leftSection}>
      {item.label}
    </Menu.Item>
  )
}
