import Button from '../components/Button'
import Box from '../components/Box'

export default function GettingStartedPage() {
  return (
    <div className="getting-started">
      <Box>
        <h1>Bem-vindo ao Registrations for WooCommerce</h1>
        <p>
          O Registrations for WooCommerce possibilita a criação do tipo de
          produto inscrição no WooCommerce. Utilizando o tipo de produto
          inscrição, é possível criar um único produto com variações
          correspondentes à datas.
        </p>
        <Button className='gradient'>Getting started</Button>
      </Box>
    </div>
  )
}
