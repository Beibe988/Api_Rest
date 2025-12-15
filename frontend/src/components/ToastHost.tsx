import { useEffect, useState } from 'react'
import { Toast, ToastContainer } from 'react-bootstrap'
import { toast$, type ToastMsg } from '../store/toast.store'

export default function ToastHost() {
  const [items, setItems] = useState<ToastMsg[]>([])

  useEffect(() => {
    const sub = toast$.subscribe(msg => {
      setItems(list => [...list, msg])
      const delay = msg.delay ?? 3000
      setTimeout(() => setItems(list => list.filter(t => t.id !== msg.id)), delay + 150)
    })
    return () => sub.unsubscribe()
  }, [])

  return (
    <ToastContainer position="top-end" className="p-3" style={{ zIndex: 1080 }}>
      {items.map(t => (
        <Toast key={t.id} bg={t.variant}>
          {t.title && (
            <Toast.Header closeButton={false}>
              <strong className="me-auto">{t.title}</strong>
            </Toast.Header>
          )}
          <Toast.Body className={t.variant === 'warning' || t.variant === 'info' ? '' : 'text-white'}>
            {t.body}
          </Toast.Body>
        </Toast>
      ))}
    </ToastContainer>
  )
}
