type Props = { value?: string | null }
export default function DateCell({ value }: Props) {
  if (!value) return <span className="text-muted">-</span>
  const d = new Date(value)
  const pretty = isNaN(+d) ? '-' : d.toLocaleString()
  return <span className="td-nowrap">{pretty}</span>
}
