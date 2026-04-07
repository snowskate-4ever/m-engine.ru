package ru.mengine.mobile.data.remote.dto

import kotlinx.serialization.Serializable

@Serializable
data class AuthRequestDto(
    val email: String,
    val password: String,
)

@Serializable
data class AuthUserDto(
    val id: Int,
    val name: String,
    val email: String,
)
