#pragma once

#include "PacketHeader.h"
#include "StaticEnums.h"

using namespace System;
using namespace System::IO;
using namespace System::Text;

#define WRITE_GENERIC_TYPEID(TYPE)			if (type == TYPE::typeid) { this->bw->Write (static_cast<TYPE>(value)); }
#define WRITE_GENERIC_TYPEID_REPEAT(TYPE)	if (type == TYPE::typeid) { for(auto i = 0; i < repeat; i++) { this->bw->Write (static_cast<TYPE>(value)); } }

namespace EloBuddy
{
	namespace Networking
	{
		ref class GamePacketEventArgs;

		public ref class GamePacket
		{
		private:
			BinaryReader^ br;
			BinaryWriter^ bw;
			MemoryStream^ ms;
			PacketHeader^ m_packetHeader;
			PacketChannel m_packetChannel;
			PacketProtocolFlags m_packetFlags;
		internal:
			void LoadData( array<byte>^ data );
			void SetPacketHeader();
			void SetPacketHeader( PacketChannel channel, PacketProtocolFlags flags );
		public:
			GamePacket();
			GamePacket( IntPtr hashAlgorithm, short header, int networkId );
			GamePacket( IntPtr hashAlgorithm, short header );
			GamePacket( Int32 hashAlgorithm, short header, int networkId );
			GamePacket( Int32 hashAlgorithm, short header );
			GamePacket( HashAlgorithm^ algorithm, short header, uint networkId );
			GamePacket( PacketHeader^ header );
			GamePacket( array<byte>^ data );
			GamePacket( GamePacketEventArgs^ args );

			void SetHeader( PacketHeader^ header );
			void Send( PacketChannel channel, PacketProtocolFlags flags );
			void Send();
			void Process( PacketChannel channel );
			void Process();

			void Dump()
			{
				System::Console::WriteLine( this->ToString() );
			}

			virtual String^ ToString() override
			{
				auto sb = gcnew StringBuilder();
				auto packetArray = this->Data;

				auto hashAlgorithm = this->Data->Length >= 4
					? BitConverter::ToInt32( this->Data, 0 )
					: 0;

				sb->Append( String::Format( "OpCode: {0} Channel: {1} - Flags: {2} - Length: {3} - HashAlgorithm: {4}", this->Header->OpCode.ToString("x2"), this->m_packetChannel, this->m_packetFlags, packetArray->Length, hashAlgorithm.ToString( "x8" ) ) );
				sb->Append( Environment::NewLine );

				for (auto i = 0; i < packetArray->Length; i++)
				{
					sb->Append( String::Format( "{0} ", packetArray [i].ToString( "x2" ) ) );
				}

				return sb->ToString();
			}

			property int Position
			{
				int get()
				{
					return this->br->BaseStream->Position;
				}
				void set(int position)
				{
					if (position > 0)
					{
						this->br->BaseStream->Position = position;
					}
				}
			}

			property array<byte>^ Data
			{
				array<byte>^ get()
				{
					return this->ms != nullptr
						? this->ms->ToArray()
						: gcnew array<byte>(0);
				}
			}

			property PacketHeader^ Header
			{
				PacketHeader^ get()
				{
					if (!m_packetHeader)
					{
						m_packetHeader = gcnew PacketHeader( this );
					}

					return m_packetHeader;
				}
			}
			generic <typename T>
			where T : value class, ValueType
			void Write( T value, int repeat, int position )
			{
				this->Position = position;
				this->Write<T>( value, repeat );
			}

			generic <typename T>
			where T : value class, ValueType
			void Write( T value )
			{
				auto type = T::typeid;
				WRITE_GENERIC_TYPEID( bool );
				WRITE_GENERIC_TYPEID( byte );
				WRITE_GENERIC_TYPEID( Int16 );
				WRITE_GENERIC_TYPEID( Int32 );
				WRITE_GENERIC_TYPEID( uint );
				WRITE_GENERIC_TYPEID( Int64 );
				WRITE_GENERIC_TYPEID( float );

				if (type == String::typeid)
				{
					this->bw->Write( Encoding::GetEncoding( "UTF-8" )->GetBytes( static_cast<String^>(Convert::ChangeType( value, String::typeid )) ) );
				}
			}

			generic <typename T>
			where T : value class, ValueType
			void Write( T value, int repeat )
			{
				auto type = T::typeid;

				WRITE_GENERIC_TYPEID_REPEAT( bool );
				WRITE_GENERIC_TYPEID_REPEAT( byte );
				WRITE_GENERIC_TYPEID_REPEAT( Int16 );
				WRITE_GENERIC_TYPEID_REPEAT( Int32 );
				WRITE_GENERIC_TYPEID_REPEAT( uint );
				WRITE_GENERIC_TYPEID_REPEAT( Int64 );
				WRITE_GENERIC_TYPEID_REPEAT( float );

				if (type == String::typeid)
				{
					this->bw->Write( Encoding::GetEncoding( "UTF-8" )->GetBytes( static_cast<String^>(Convert::ChangeType( value, String::typeid )) ) );
				}
			}

			generic <typename T>
			where T : value class
			T Read( int position )
			{
				this->Position = position;
				return (T) this->Read<T>();
			}

			generic <typename T>
			where T : value class
			T Read( )
			{
				auto type = T::typeid;

				if (type == bool::typeid || type == byte::typeid)
				{
					return (T) this->br->ReadBytes( 1 ) [0];
				}

				if (type == Int16::typeid || type == short::typeid)
				{
					return (T) BitConverter::ToInt16( this->br->ReadBytes( 2 ), 0 );
				}

				if (type == Int32::typeid)
				{
					return (T) BitConverter::ToInt32( this->br->ReadBytes( 4 ), 0 );
				}

				if (type == Int64::typeid)
				{
					return (T) BitConverter::ToInt64( this->br->ReadBytes( 8 ), 0 );
				}

				if (type == float::typeid)
				{
					return (T) BitConverter::ToSingle( this->br->ReadBytes( 4 ), 0 );
				}

				return T();
			}
		};
	}
}