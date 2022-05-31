import './box.scss'

export default function Box({ children, className = '' }) {
  return <div className={`haste-box ${className}`}>{children}</div>
}
