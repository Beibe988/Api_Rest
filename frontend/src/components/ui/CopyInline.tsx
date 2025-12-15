import { useState } from 'react'

export default function CopyInline({ text }: { text: string }) {
  const [ok, setOk] = useState(false)
  async function copy() {
    try {
      await navigator.clipboard.writeText(text)
      setOk(true)
      setTimeout(() => setOk(false), 1200)
    } catch {}
  }
  return (
    <button
      type="button"
      className="btn btn-link btn-sm p-0 ms-2 align-baseline"
      onClick={copy}
      title="Copia"
    >
      {ok ? '✓' : '📋'}
    </button>
  )
}
