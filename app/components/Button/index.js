import './button.scss'

export default function Button({
  children,
  type = 'button',
  className = '',
  size = 'normal'
}) {
  return (
    <button type={type} className={`haste-btn ${className} ${size}`}>
      {children}
    </button>
  )
}
