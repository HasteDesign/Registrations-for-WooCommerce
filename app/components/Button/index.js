import './button.scss'

export default function Button({
  children,
  type = 'button',
  className = '',
  size = 'normal',
  onClick = null
}) {
  return (
    <button type={type} className={`haste-btn ${className} ${size}`} onClick={onClick}>
      {children}
    </button>
  )
}
