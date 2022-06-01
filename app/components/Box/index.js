import './box.scss'

export default function Box({ children, className = '', size = '' }) {
  return <div className={`haste-box ${className} ${size}`}>{children}</div>
}
