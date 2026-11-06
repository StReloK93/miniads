import { Component } from "vue";
import { z } from "zod";

export interface ICategory {
   name: string;
   slug: string;
   id: number;
   image: string;
   products: IProduct[];
   is_page: boolean;
   with_price: boolean;
   with_image: boolean;
   /** false — e'lon formasida sarlavha so'ralmaydi */
   with_title?: boolean;
   /** E'lonlari hech qachon tugamaydi */
   is_permanent?: boolean;
   /** Bosh sahifadagi "Tezkor bo'limlar" qatorida chiqadi */
   is_featured?: boolean;
   parent: ICategory;
   parent_id: number | null;
   /** Asosiy bo'lim id'si (Uy-joy, Avto...) — server hisoblaydi */
   root_id?: number;
   parameters: IParameter[];
   children: ICategory[];
}

export interface ICategoryParameter {
   id: number;
   parameter_id: number;
   category_id: number;
   is_required: boolean;
   sort_order: number;
}

export interface InputConfig {
   component: Component;
   props?: IProps | null;
   generateProps?: () => Promise<void>;
   name: string;
   placeholder?: string;
   schema: z.ZodTypeAny;
   class?: string[];
   value?: any;
   selectOption?: IProps;
   teleport_child_class?: string;
   teleport_parent_class?: string;
   required?: boolean;
   optional?: boolean;
   /** Maydon ostidagi kichik izoh */
   hint?: string;
}

export interface IProps {
   [key: string]: unknown;
}

export interface IParameterOption {
   id: number;
   value: string;
}

export interface IParameter {
   id: number;
   title: string;
   placeholder: string;
   component: string;
   type: string;
   pivot: ICategoryParameter;
   /** Foydalanuvchi tilidagi birlik (ko'rsatish uchun) */
   unit: string | null;
   /** Asl (o'zbekcha) birlik — mantiq uchun: "yil", "-qavat" */
   unit_code?: string | null;
   /** Asl variantlar: e'londa shular saqlanadi */
   options: IParameterOption[];
   /** options bilan bir tartibda — foydalanuvchi tilidagi nomlari */
   option_labels?: string[] | null;
}

export interface IProduct {
   id: number;
   title: string;
   description: string;
   price: number | null;
   back_color: { id: number; gradient: string; border: string };
   price_type: { type: string; name: string; position: "right" | "left" };
   phone: string | null;
   category_id: number;
   views_count: number;
   category: ICategory;
   parameter_values: IParameterValue[];
   images: {
      id: number;
      product_id: number;
      src: string;
      crop_src: string | null;
      // Ro'yxat kartalari uchun kichik nusxa (yo'q bo'lsa src)
      thumb_src: string | null;
      crop_x: number;
      crop_y: number;
      crop_scale: number;
   }[];
   is_favorite: boolean;
   created_at: string;
   district: { id: number; name: string };
   district_id: number;
   updated_at: string;
   /** null — muddatsiz e'lon (hech qachon o'chmaydi) */
   expires_at: string | null;
   published_at: string;
   user: IUser;
   user_id: number;
   /** Egasi "Sotildi" deb to'xtatgan vaqt */
   sold_at?: string | null;
   /** Muddatsiz e'lon (expires_at = null) — sahifada "Muddatsiz" belgisi */
   is_permanent?: boolean;
   /** Telegram assistent (bot) kanaldan olgan e'lon */
   is_bot?: boolean;
   days: {
      /** Muddatsiz e'londa null */
      current: number | null;
      max: number | null;
      /** Muddatsiz e'lon — "N kun qoldi" o'rniga "Muddatsiz" */
      permanent?: boolean;
   };
   similar_products?: IProduct[];
   /** Shu sotuvchining boshqa faol e'lonlari (bot e'lonlarida bo'sh) */
   seller_products?: IProduct[];
}

// E'lon yaratish/yangilash uchun API'ga yuboriladigan ma'lumot (multipart/form-data)
export interface IProductPayload {
   title: string;
   description?: string | null;
   phone: string | null;
   price?: number | null;
   price_type_id?: number | null;
   back_color_id?: number | null;
   category_id: number | string | undefined;
   district_id: number | null;
   parameters: { id: string | number; value: unknown }[];
   images?: unknown[];
}

export interface IUser {
   id: number;
   name: string;
   username: string | null;
   telegram_user_id: number | null;
   role: "user" | "admin";
   /** Admin bloklagan bo'lsa — e'lon joylay/tahrirlay olmaydi */
   blocked_at?: string | null;
   /** Bot shaxsiy xabar yubora oladimi (ruxsat bergan yoki /start bosgan) */
   bot_can_message?: boolean;
   notify_saved_searches?: boolean;
   notify_expiry?: boolean;
   /** Sevimlilardagi e'lon arzonlasa bot xabar beradi */
   notify_price_drop?: boolean;
   /** Yangi e'lon formasida avtomatik to'ldiriladi */
   phone?: string | null;
   active_district_id: number | null;
   active_district?: IDistrict | null;
}

export interface ISavedSearch {
   id: number;
   query: string;
   label: string;
   /** Ilovadagi filtr oynasi holati (features/filters/listFilters → ListFilters) */
   filters: Record<string, any> | null;
   /** Server filtrlari (toQueryParams natijasi) */
   params: Record<string, any> | null;
   is_paused: boolean;
   last_notified_at: string | null;
   created_at: string;
}

export interface IParameterValue {
   id: number;
   parameter: IParameter;
   parameter_id: number;
   product_id: number;
   value: string;
   /** Tanlov qiymatining foydalanuvchi tilidagi nomi (server) */
   display_value?: string | null;
}

export interface IParameter {
   id: number;
   placeholder: string;
   type: string;
   pivot: ICategoryParameter;
   unit: string | null;
   options: IParameterOption[];
}

export interface ITreeNode {
   id: number;
   title: string;
   children?: ITreeNode[];
   parent_id?: number | null;
   image?: string;
   is_page?: boolean;
   draggable?: boolean; // o'zi ko'chiriladimi
   droppable?: boolean; // ichiga tashlash mumkinmi
}

export interface IDistrict {
   id: number;
   name: string;
}

export interface IPaginatedResponse<T> {
   data: T[];
   current_page: number;
   last_page: number;
   per_page: number;
   total: number;
   next_page_url: string | null;
   prev_page_url: string | null;
}
