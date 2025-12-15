export type Role = 'Guest' | 'User' | 'Admin'

export type Gender = 'M' | 'F'   // M = uomo, F = donna

// -------------------- PERSONAL --------------------
export interface PersonalData {
  name: string
  surname: string
  email: string
  displayName?: string
  birthDate: string              
  gender: Gender                 
  birthCity: string              
  birthProvince: string          
  fiscalCode: string             
  phone?: string
}

// -------------------- ADDRESS --------------------
export interface AddressData {
  street: string
  city: string
  province: string
  postalCode: string
  country: string
}

// -------------------- CREDENTIALS --------------------
export interface Credentials {
  password: string
  confirmPassword: string
}

// -------------------- FORM AGGREGATO --------------------
export interface RegistrationForm {
  personal: PersonalData
  address: AddressData
  credentials: Credentials
}

// -------------------- DTO API (snake_case, piatto) --------------------
export interface RegistrationDTO {
  name: string
  surname: string
  email: string
  display_name?: string
  birth_date: string
  gender: Gender
  birth_city: string
  birth_province: string
  fiscal_code: string
  phone?: string
  street: string
  city: string
  province: string
  postal_code: string
  country: string
  password: string
  password_confirmation: string
}

// -------------------- MAPPER --------------------
export class RegistrationData {
  constructor(public form: RegistrationForm) {}

  toDTO(): RegistrationDTO {
    const { personal, address, credentials } = this.form
    return {
      name: personal.name.trim(),
      surname: personal.surname.trim(),
      email: personal.email.trim().toLowerCase(),
      display_name: personal.displayName?.trim() || undefined,

      // OBBLIGATORI
      birth_date: personal.birthDate,
      gender: personal.gender,
      birth_city: personal.birthCity.trim(),
      birth_province: personal.birthProvince.trim().toUpperCase(),
      fiscal_code: personal.fiscalCode.trim().toUpperCase(),

      phone: personal.phone?.trim() || undefined,

      // indirizzo (piatto)
      street: address.street.trim(),
      city: address.city.trim(),
      province: address.province.trim(),
      postal_code: address.postalCode.trim(),
      country: address.country.trim(),

      password: credentials.password,
      password_confirmation: credentials.confirmPassword,
    }
  }
}


