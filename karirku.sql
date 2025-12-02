-- WARNING: This schema is for context only and is not meant to be run.
-- Table order and constraints may not be valid for execution.

CREATE TABLE public.chat (
  id_chat integer NOT NULL DEFAULT nextval('chat_id_chat_seq'::regclass),
  id_pengirim integer,
  id_penerima integer,
  pesan text NOT NULL,
  dikirim_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  sudah_dibaca boolean DEFAULT false,
  CONSTRAINT chat_pkey PRIMARY KEY (id_chat),
  CONSTRAINT fk_chat_pengirim FOREIGN KEY (id_pengirim) REFERENCES public.pengguna(id_pengguna),
  CONSTRAINT fk_chat_penerima FOREIGN KEY (id_penerima) REFERENCES public.pengguna(id_pengguna)
);
CREATE TABLE public.cv (
  id_cv integer NOT NULL DEFAULT nextval('cv_id_cv_seq'::regclass),
  id_pencaker integer,
  nama_file character varying NOT NULL,
  cv_url text,
  uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  id_pengguna integer,
  CONSTRAINT cv_pkey PRIMARY KEY (id_cv),
  CONSTRAINT cv_id_pengguna_fkey FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna)
);
CREATE TABLE public.favorit_lowongan (
  id_favorit integer NOT NULL DEFAULT nextval('favorit_lowongan_id_favorit_seq'::regclass),
  id_pencaker integer,
  id_lowongan integer,
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT favorit_lowongan_pkey PRIMARY KEY (id_favorit),
  CONSTRAINT fk_favorit_lowongan FOREIGN KEY (id_lowongan) REFERENCES public.lowongan(id_lowongan),
  CONSTRAINT fk_favorit_pencaker FOREIGN KEY (id_pencaker) REFERENCES public.pencaker(id_pencaker)
);
CREATE TABLE public.lamaran (
  id_lamaran integer NOT NULL DEFAULT nextval('lamaran_id_lamaran_seq'::regclass),
  id_lowongan integer,
  id_pencaker integer,
  cv_url character varying,
  cover_letter text,
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT lamaran_pkey PRIMARY KEY (id_lamaran),
  CONSTRAINT fk_lamaran_lowongan FOREIGN KEY (id_lowongan) REFERENCES public.lowongan(id_lowongan),
  CONSTRAINT fk_lamaran_pencaker FOREIGN KEY (id_pencaker) REFERENCES public.pencaker(id_pencaker)
);
CREATE TABLE public.laporan (
  id bigint GENERATED ALWAYS AS IDENTITY NOT NULL,
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT laporan_pkey PRIMARY KEY (id)
);
CREATE TABLE public.lowongan (
  id_lowongan integer NOT NULL DEFAULT nextval('lowongan_id_lowongan_seq'::regclass),
  id_perusahaan integer,
  judul character varying NOT NULL,
  deskripsi text NOT NULL,
  kualifikasi text,
  lokasi character varying,
  tipe_pekerjaan character varying CHECK (tipe_pekerjaan::text = ANY (ARRAY['full-time'::character varying, 'part-time'::character varying, 'contract'::character varying, 'internship'::character varying]::text[])),
  gaji_range character varying,
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  batas_tanggal date,
  status character varying DEFAULT 'open'::character varying CHECK (status::text = ANY (ARRAY['open'::character varying, 'closed'::character varying]::text[])),
  kategori character varying,
  mode_kerja character varying DEFAULT 'On-site'::character varying CHECK (mode_kerja::text = ANY (ARRAY['On-site'::character varying, 'Hybrid'::character varying, 'Remote'::character varying, 'Shift'::character varying, 'Lapangan'::character varying]::text[])),
  benefit text,
  CONSTRAINT lowongan_pkey PRIMARY KEY (id_lowongan),
  CONSTRAINT lowongan_id_perusahaan_fkey FOREIGN KEY (id_perusahaan) REFERENCES public.perusahaan(id_perusahaan)
);
CREATE TABLE public.notifikasi (
  id_notifikasi integer NOT NULL DEFAULT nextval('notifikasi_id_notifikasi_seq'::regclass),
  id_pengguna integer,
  pesan text NOT NULL,
  tipe character varying CHECK (tipe::text = ANY (ARRAY['lamaran'::character varying, 'pesan'::character varying, 'system'::character varying]::text[])),
  sudah_dibaca boolean DEFAULT false,
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT notifikasi_pkey PRIMARY KEY (id_notifikasi),
  CONSTRAINT fk_notifikasi_pengguna FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna)
);
CREATE TABLE public.pencaker (
  id_pencaker integer NOT NULL DEFAULT nextval('pencaker_id_pencaker_seq'::regclass),
  id_pengguna integer UNIQUE,
  tanggal_lahir date,
  gender character varying CHECK (gender::text = ANY (ARRAY['male'::character varying, 'female'::character varying, 'other'::character varying]::text[])),
  alamat text,
  pengalaman_tahun integer,
  cv_url character varying,
  cv_parsed jsonb,
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  foto_profil_url character varying,
  foto_profil_path character varying,
  nama_lengkap text,
  no_hp character varying,
  email_pencaker character varying,
  CONSTRAINT pencaker_pkey PRIMARY KEY (id_pencaker),
  CONSTRAINT fk_pencaker_pengguna FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna)
);
CREATE TABLE public.pencaker_skill (
  id_pencaker integer NOT NULL,
  id_skill integer NOT NULL,
  level character varying DEFAULT 'beginner'::character varying CHECK (level::text = ANY (ARRAY['beginner'::character varying, 'intermediate'::character varying, 'expert'::character varying]::text[])),
  CONSTRAINT pencaker_skill_pkey PRIMARY KEY (id_pencaker, id_skill),
  CONSTRAINT fk_ps_pencaker FOREIGN KEY (id_pencaker) REFERENCES public.pencaker(id_pencaker),
  CONSTRAINT fk_ps_skill FOREIGN KEY (id_skill) REFERENCES public.skill(id_skill)
);
CREATE TABLE public.pengguna (
  id_pengguna integer NOT NULL DEFAULT nextval('pengguna_id_pengguna_seq'::regclass),
  nama_lengkap character varying NOT NULL,
  email character varying NOT NULL UNIQUE,
  password character varying NOT NULL,
  no_hp character varying,
  role character varying CHECK (role::text = ANY (ARRAY['pencaker'::character varying, 'perusahaan'::character varying, 'admin'::character varying]::text[])),
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  diperbarui_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  email_verified boolean DEFAULT false,
  auth_user_id uuid,
  foto_url text,
  CONSTRAINT pengguna_pkey PRIMARY KEY (id_pengguna),
  CONSTRAINT pengguna_auth_user_id_fkey FOREIGN KEY (auth_user_id) REFERENCES auth.users(id)
);
CREATE TABLE public.perusahaan (
  id_perusahaan integer NOT NULL DEFAULT nextval('perusahaan_id_perusahaan_seq'::regclass),
  id_pengguna integer UNIQUE,
  nama_perusahaan character varying NOT NULL,
  deskripsi text,
  website character varying,
  lokasi character varying,
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  status_persetujuan character varying DEFAULT '''menunggu'''::character varying,
  CONSTRAINT perusahaan_pkey PRIMARY KEY (id_perusahaan),
  CONSTRAINT fk_perusahaan_pengguna FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna)
);
CREATE TABLE public.riwayat_notifikasi (
  id_riwayat_notifikasi integer NOT NULL DEFAULT nextval('riwayat_notifikasi_id_riwayat_notifikasi_seq'::regclass),
  id_pengguna integer,
  pesan text NOT NULL,
  tipe character varying CHECK (tipe::text = ANY (ARRAY['lamaran'::character varying, 'pesan'::character varying, 'system'::character varying]::text[])),
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT riwayat_notifikasi_pkey PRIMARY KEY (id_riwayat_notifikasi),
  CONSTRAINT fk_riwayat_notifikasi_pengguna FOREIGN KEY (id_pengguna) REFERENCES public.pengguna(id_pengguna)
);
CREATE TABLE public.riwayat_pencarian (
  id_pencarian integer NOT NULL DEFAULT nextval('riwayat_pencarian_id_pencarian_seq'::regclass),
  id_pencaker integer,
  keyword character varying,
  lokasi character varying,
  tipe_pekerjaan character varying CHECK (tipe_pekerjaan::text = ANY (ARRAY['full-time'::character varying, 'part-time'::character varying, 'contract'::character varying, 'internship'::character varying]::text[])),
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT riwayat_pencarian_pkey PRIMARY KEY (id_pencarian),
  CONSTRAINT fk_riwayat_pencarian_pencaker FOREIGN KEY (id_pencaker) REFERENCES public.pencaker(id_pencaker)
);
CREATE TABLE public.riwayat_status_lamaran (
  id_status integer NOT NULL DEFAULT nextval('riwayat_status_lamaran_id_status_seq'::regclass),
  id_lamaran integer,
  status character varying CHECK (status::text = ANY (ARRAY['applied'::character varying, 'rejected'::character varying]::text[])),
  catatan text,
  dibuat_pada timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT riwayat_status_lamaran_pkey PRIMARY KEY (id_status),
  CONSTRAINT fk_status_lamaran FOREIGN KEY (id_lamaran) REFERENCES public.lamaran(id_lamaran)
);
CREATE TABLE public.skill (
  id_skill integer NOT NULL DEFAULT nextval('skill_id_skill_seq'::regclass),
  nama_skill character varying UNIQUE,
  CONSTRAINT skill_pkey PRIMARY KEY (id_skill)
);