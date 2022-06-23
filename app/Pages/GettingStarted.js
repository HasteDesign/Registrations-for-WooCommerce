import Button from '../components/Button'
import Box from '../components/Box'

const { useState } = wp.element

export default function GettingStartedPage() {
  const [start, setStart] = useState(false)
  function isStartedHandle() {
    return setStart(true)
  }
  return (
    <div className="getting-started">
      {!start ? (
        <Box size="large">
          <h1>Bem-vindo ao Registrations for WooCommerce</h1>
          <p className="paragraph-start">
            O Registrations for WooCommerce possibilita a criação do tipo de
            produto inscrição no WooCommerce. Utilizando o tipo de produto
            inscrição, é possível criar um único produto com variações
            correspondentes à datas.
          </p>
          <Button className="gradient" size="large" onClick={isStartedHandle}>
            Getting started
          </Button>
        </Box>
      ) : (
        <Box size="large">
          <div>
            <img
              src={assets_path + 'icon-128x128.png'}
              alt="Logo do Registrations"
            />
            <h1>Registrations for WooCommerce</h1>
          </div>
          <p className='paragraaph-start'>
            Olá! Gostaríamos de solicitar autorização para recebermos alguns
            dados referentes ao seu uso do nosso plugin. Caso você aceite, nosso
            time poderá continuamente aprimorar o Registrations! Também
            compartilharemos com você novidades via email.
          </p>
          <div>
            <Button className='gradient'>
            Permitir e continuar
            </Button>
            <Button className='outline'>
              Pular
            </Button>
          </div>
        </Box>
      )}
    </div>
  )
}
