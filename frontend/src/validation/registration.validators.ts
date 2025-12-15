import type { RegistrationForm } from '../types/registration'
import type { ErrorMap } from '../errors/validation'
import { isValidCodiceFiscale, normalizeCF } from '../lib/it/codiceFiscale'

const EMAIL_RX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const POSTAL_RX = /^[A-Za-z0-9 -]{3,12}$/
const BIRTH_PROV_RX = /^[A-Z]{2,3}$/ // es. RM, MI (tollera 2-3 char)
const MIN_PASS = 8

export function validateRegistration(form: RegistrationForm): ErrorMap {
  const e: ErrorMap = {}
  const p = form.personal
  const a = form.address
  const c = form.credentials

  // --- PERSONALI (tutti obbligatori) ---
  if (!p.name?.trim()) e['personal.name'] = 'Il nome è obbligatorio'
  if (!p.surname?.trim()) e['personal.surname'] = 'Il cognome è obbligatorio'

  if (!p.email?.trim()) e['personal.email'] = 'L’email è obbligatoria'
  else if (!EMAIL_RX.test(p.email.trim())) e['personal.email'] = 'Email non valida'

  if (!p.birthDate?.trim()) e['personal.birthDate'] = 'La data di nascita è obbligatoria'

  if (!p.gender?.trim()) e['personal.gender'] = 'Il sesso è obbligatorio'
  else if (!(p.gender === 'M' || p.gender === 'F')) e['personal.gender'] = 'Valore non valido'

  if (!p.birthCity?.trim()) e['personal.birthCity'] = 'Il comune di nascita è obbligatorio'

  if (!p.birthProvince?.trim()) e['personal.birthProvince'] = 'La provincia di nascita è obbligatoria'
  else if (!BIRTH_PROV_RX.test(p.birthProvince.trim().toUpperCase())) {
    e['personal.birthProvince'] = 'Provincia non valida (es. RM)'
  }

  if (!p.fiscalCode?.trim()) e['personal.fiscalCode'] = 'Il codice fiscale è obbligatorio'
  else {
    const cf = normalizeCF(p.fiscalCode)
    if (!isValidCodiceFiscale(cf)) {
      e['personal.fiscalCode'] = 'Codice fiscale non valido'
    }
    // Nota: la coerenza con nome/cognome/data/sesso/comune può essere aggiunta
    // se in futuro esponiamo un helper dedicato (qui evitiamo dipendenze non esistenti).
  }

  // --- RESIDENZA (tutta opzionale) ---
  // Niente errori se vuoti. Se presenti, validiamo "soft".
  if (a.postalCode?.trim() && !POSTAL_RX.test(a.postalCode.trim())) {
    e['address.postalCode'] = 'CAP non valido'
  }
  // per province/residenza non imponiamo formato rigido: l’input verrà uppercased a UI

  // --- CREDENZIALI (obbligatorie) ---
  if (!c.password) e['credentials.password'] = 'Password obbligatoria'
  else if (c.password.length < MIN_PASS) e['credentials.password'] = `Minimo ${MIN_PASS} caratteri`

  if (!c.confirmPassword) e['credentials.confirmPassword'] = 'Conferma password obbligatoria'
  else if (c.confirmPassword !== c.password) e['credentials.confirmPassword'] = 'Le password non coincidono'

  return e
}

