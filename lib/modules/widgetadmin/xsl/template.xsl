<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <!-- таблица маршрутизации -->
  <xsl:template match="content[@name='widgets']" mode="content">
    <div id="routelist">
      <h2>Виджеты</h2>
      <xsl:choose>
        <xsl:when test="not(widget)">
          <p>Ни одного виджета пока нет, <a href="admin/structure/widgets/add">создать</a>?</p>
        </xsl:when>
        <xsl:otherwise>
          <form method="get" action="{@base}/delete">
            <table>
              <thead>
                <tr>
                  <th />
                  <th>Имя</th>
                  <th>Заголовок</th>
                  <th>Тип</th>
                  <th />
                </tr>
              </thead>
              <tbody>
                <xsl:for-each select="widget">
                  <xsl:sort select="@name" />
                  <tr>
                    <xsl:if test="@disabled">
                      <xsl:attribute name="class">
                        <xsl:text>disabled</xsl:text>
                      </xsl:attribute>
                    </xsl:if>
                    <td>
                      <input type="checkbox" name="delete[]" value="{@name}" />
                    </td>
                    <td class="path">
                      <a href="{../@base}/edit?name={@name}">
                        <xsl:value-of select="@name" />
                      </a>
                    </td>
                    <td class="title">
                      <xsl:value-of select="@title" />
                    </td>
                    <td>
                      <xsl:choose>
                        <xsl:when test="info/@name and info/@docurl">
                          <a href="{info/@docurl}">
                            <xsl:value-of select="info/@name" />
                          </a>
                        </xsl:when>
                        <xsl:when test="info/@name">
                          <xsl:value-of select="info/@name" />
                        </xsl:when>
                        <xsl:otherwise>
                          <xsl:value-of select="@classname" />
                        </xsl:otherwise>
                      </xsl:choose>
                    </td>
                    <td>
                      <xsl:if test="@orphan">
                        <em>виджет не используется</em>
                      </xsl:if>
                    </td>
                  </tr>
                </xsl:for-each>
              </tbody>
            </table>
            <p><input type="submit" value="Удалить отмеченные" /> или <a  href="{@base}/add">добавить новый виджет</a>.</p>
          </form>
          <p>Вся изменяемая здесь информация хранится в файле <tt><xsl:value-of select="/page/@sitefolder" />/widgets.ini</tt>, который можно редактировать вручную.</p>
        </xsl:otherwise>
      </xsl:choose>
    </div>
  </xsl:template>

  <xsl:template match="content[@name='delete-widgets']" mode="content">
    <div id="routelist">
      <h2>Удаление виджетов</h2>
      <p>Следующие виджеты более не нужны, и их следует удалить:</p>
      <ol>
        <xsl:for-each select="widget">
          <li class="tt">
            <xsl:value-of select="@name" />
          </li>
        </xsl:for-each>
      </ol>

      <form method="post" action="{@base}/delete">
        <xsl:for-each select="widget">
          <input type="hidden" name="delete[]" value="{@name}" />
        </xsl:for-each>
        <label>
          <input type="checkbox" name="confirm" value="1" />
          <span>Подтверждаю</span>
        </label>
        <p><input type="submit" value="Удалить" /></p>
      </form>
    </div>
  </xsl:template>
</xsl:stylesheet>
